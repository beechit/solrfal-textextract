<?php

namespace BeechIt\SolrfalTextextract\Aspects;

/*
 * This source file is proprietary property of Beech Applications B.V.
 * Date: 1-04-2015 11:07
 * All code (c) Beech Applications B.V. all rights reserved
 */

use ApacheSolrForTypo3\Solrfal\Queue\Item;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Class SolrFalAspect
 */
class SolrFalAspect implements SingletonInterface
{
    /**
     * @var string
     */
    protected $pathTika = '/usr/bin';

    /**
     * @var string
     */
    protected $pathPdftotext;

    /**
     * @var array
     */
    protected $supportedFileExtensions = [];

    /**
     * @var bool
     */
    protected $debug = true;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * Contructor
     */
    public function __construct()
    {
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);

        $extConf = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['solrfal_textextract'];
        if (!empty($extConf['pathTika'])) {
            $this->pathTika = $extConf['pathTika'];

            if (!\TYPO3\CMS\Core\Utility\PathUtility::isAbsolutePath($this->pathTika)) {
                $this->pathTika = PathUtility::getCanonicalPath(Environment::getPublicPath() . '/' . $this->pathTika);
            }

            if (!@is_file($this->pathTika)) {
                $this->pathTika = GeneralUtility::getFileAbsFileName($this->pathTika, false);
            }

            if (!@is_file($this->pathTika)) {
                $this->pathTika = null;
            }
        }
        if (!empty($extConf['pathPdftotext'])) {
            $this->pathPdftotext = $extConf['pathPdftotext'];
        }
        if (!empty($extConf['supportedFileExtensions'])) {
            $this->supportedFileExtensions = GeneralUtility::trimExplode(',', $extConf['supportedFileExtensions']);
        }
        $this->debug = !empty($extConf['debugMode']);

        if ($this->debug) {
            $messages = [];
            if (!$this->pathTika) {
                $messages[] = 'Tika jar not found.';
            }
            if (!$this->pathPdftotext) {
                $messages[] = 'No pdftotext path set';
            }
            if ($this->supportedFileExtensions === []) {
                $messages[] = 'No supported file extensions set';
            }
            if ($messages !== []) {
                $this->logger->error('Configuration error: ' . implode(',', $messages));
            }
        }
    }

    /**
     * Add correct fe_group info and public_url
     *
     * @param Item $item
     * @param \ArrayObject $metadata
     */
    public function fileMetaDataRetrieved(Item $item, \ArrayObject $metadata)
    {
        if ($item->getFile() instanceof File && in_array(mb_strtolower($item->getFile()->getExtension()), $this->supportedFileExtensions)) {
            $content = null;
            if ($item->getFile()->getExtension() === 'pdf') {
                $content = $this->pdfToText($item->getFile());
            }
            if ($content === null && $this->pathTika) {
                $content = $this->fileToText($item->getFile());
            }
            if ($content !== null) {
                $metadata['content'] = $content;
                $this->logger->debug('Parsed content of ' . $item->getFile()->getIdentifier());
            } else {
                $this->logger->debug('Could not parse ' . $item->getFile()->getIdentifier());
            }
        } elseif ($item->getFile() instanceof File) {
            $this->logger->debug('Could not parsed content of ' . $item->getFile()->getIdentifier() . ' unsupported file extension');
        }
    }

    /**
     * Use pdftotext to extract contents of pdf file
     *
     * @param File $file
     * @return string
     */
    protected function pdfToText(File $file)
    {
        if ($this->isPdfEncrypted($file)) {
            return '';
        }
        $tempFile = GeneralUtility::tempnam('pdfToText');
        $cmd = rtrim($this->pathPdftotext, '/') . '/pdftotext -enc UTF-8 -q '
            . escapeshellarg($file->getForLocalProcessing(false))
            . ' ' . $tempFile;
        exec($cmd);
        $content = file_get_contents($tempFile);
        GeneralUtility::unlink_tempfile($tempFile);

        // Last check for encrypted document
        if ($this->textHasEncryptionMarks($content)) {
            $content = null;
        }

        return $content;
    }

    /**
     * Check if pdf is encrypted
     *
     * @param File $file
     * @return bool
     */
    protected function isPdfEncrypted(File $file)
    {
        $encrypted = false;
        $cmd = rtrim($this->pathPdftotext, '/') . '/pdfinfo '
            . escapeshellarg($file->getForLocalProcessing(false));
        exec($cmd, $pdfInfoArray);

        $form = '';
        $version = 0;
        $copyEncrypted = false;
        $changeEncrypted = false;
        $optimized = false;
        $pageFormatA4 = false;

        // Find some info about pdf to determine if we can read its contents
        foreach ($pdfInfoArray as $line) {
            [$key, $value] = explode(':', $line, 2);
            $value = trim($value);

            if ($key === 'Encrypted') {
                if ($value !== 'no') {
                    $encrypted = true;
                    $copyEncrypted = !str_contains($value, 'copy:no');
                    $changeEncrypted = !str_contains($value, 'change:no');
                }
            }

            if ($key === 'Form') {
                $form = $value;
            }
            if ($key === 'PDF version') {
                $version = (float)$value;
            }
            if ($key === 'Optimized') {
                $optimized = ($value === 'Yes');
            }
            if ($key === 'Page size') {
                $pageFormatA4 = (!str_contains($value, 'A4'));
            }
        }

        // Forms are readable (AcroForm we know for sure, but we expect all forms)
        if ($form !== 'none') {
            $encrypted = false;
        }
        // Version < 1.6 can also be read if copy of change isn't encrypted
        if ($version < 1.6 && (!$copyEncrypted || !$changeEncrypted)) {
            $encrypted = false;
        }

        // PDF version 1.6 is also readable for if not optimized
        if ($version === 1.6 && !$optimized) {
            $encrypted = false;
        }
        // PDF version 1.6 and no A4 value is also (most times) readable
        if ($version === 1.6 && !$pageFormatA4) {
            $encrypted = false;
        }

        return $encrypted;
    }

    /**
     * Check if text has markers that are consistent with an encrypted document
     *
     * @param $text
     * @return bool
     */
    protected function textHasEncryptionMarks($text)
    {
        if (str_contains($text, '%#$#')) {
            return true;
        }
        if (str_contains($text, '!%!')) {
            return true;
        }
        return false;
    }

    /**
     * Use tika to extract contents of pdf file
     *
     * @param File $file
     * @return string
     */
    protected function fileToText(File $file)
    {
        $content = null;
        $tikaCommand = 'java'
            . ' -Dfile.encoding=UTF8' // forces UTF8 output
            . ' -jar ' . escapeshellarg($this->pathTika)
            . ' -t'
            . ' ' . escapeshellarg($file->getForLocalProcessing(false));

        exec($tikaCommand, $output);

        if ($output) {
            $content = implode(PHP_EOL, $output);

            // Last check for encrypted document
            if ($this->textHasEncryptionMarks($content)) {
                $content = null;
            }
        }

        return $content;
    }
}

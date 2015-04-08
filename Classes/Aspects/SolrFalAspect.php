<?php
namespace BeechIt\SolrfalTextextract\Aspects;

/*
 * This source file is proprietary property of Beech Applications B.V.
 * Date: 1-04-2015 11:07
 * All code (c) Beech Applications B.V. all rights reserved
 */

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Utility\CommandUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use BeechIt\FalSecuredownload\Security\CheckPermissions;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Utility\DebuggerUtility;
use TYPO3\Solr\Solrfal\Queue\Item;

/**
 * Class SolrFalAspect
 */
class SolrFalAspect implements SingletonInterface {

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
	protected $supportedFileExtensions = array();

	/**
	 * @var bool
	 */
	protected $debug = TRUE;

	/**
	 * Contructor
	 */
	public function __construct() {
		$extConf = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['solrfal_textextract']);
		if (!empty($extConf['pathTika'])) {
			$this->pathTika = $extConf['pathTika'];
			if (!GeneralUtility::isAbsPath($this->pathTika)) {
				$this->pathTika = PathUtility::getCanonicalPath(PATH_site . $this->pathTika);
			}
			$this->pathTika = GeneralUtility::getFileAbsFileName($this->pathTika, FALSE);
			if (!@is_file($this->pathTika)) {
				$this->pathTika = NULL;
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
			$messages = array();
			if (!$this->pathTika) {
				$messages[] = 'Tika jar not found.';
			}
			if (!$this->pathPdftotext) {
				$messages[] = 'No pdftotext path set';
			}
			if ($this->supportedFileExtensions === array()) {
				$messages[] = 'No supported file extensions set';
			}
			if ($messages !== array()) {
				$this->writeLog('Configuration error: ' . implode(',', $messages), 3);
			}
		}
	}

	/**
	 * Add correct fe_group info and public_url
	 *
	 * @param Item $item
	 * @param \ArrayObject $metadata
	 */
	public function fileMetaDataRetrieved(Item $item, \ArrayObject $metadata) {

		if ($item->getFile() instanceof File && in_array(mb_strtolower($item->getFile()->getExtension()), $this->supportedFileExtensions)) {
			$content = NULL;
			if ($item->getFile()->getExtension() === 'pdf') {
				$content = $this->pdfToText($item->getFile());
			} elseif ($this->pathTika) {
				$content = $this->fileToText($item->getFile());
			}
			if ($content !== NULL) {
				$metadata['content'] = $content;
			}
		}
	}

	/**
	 * Use pdftotext to extract contents of pdf file
	 *
	 * @param File $file
	 * @return string
	 */
	protected function pdfToText(File $file) {
		if ($this->isPdfEncrypted($file)) {
			return '';
		}
		$tempFile = GeneralUtility::tempnam('pdfToText');
		$cmd = rtrim($this->pathPdftotext, '/') . '/pdftotext -enc UTF-8 -q '
			  . escapeshellarg($file->getForLocalProcessing(FALSE))
			  . ' ' . $tempFile;
		exec($cmd);
		$content = file_get_contents($tempFile);
		GeneralUtility::unlink_tempfile($tempFile);
		return $content;
	}

	/**
	 * Check if pdf is encrypted
	 *
	 * @param File $file
	 * @return bool
	 */
	protected function isPdfEncrypted(File $file) {
		$encrypted = FALSE;
		$cmd = rtrim($this->pathPdftotext, '/') . '/pdfinfo '
			. escapeshellarg($file->getForLocalProcessing(FALSE));
		CommandUtility::exec($cmd, $pdfInfoArray);

		$form = '';
		$version = 0;
		$copyEncrypted = FALSE;
		$changeEncrypted = FALSE;
		$optimized = FALSE;
		$pageFormatA4 = FALSE;

		// Find some info about pdf to determine if we can read its contents
		foreach ($pdfInfoArray as $line) {
			list($key, $value) = explode(':', $line, 2);
			$value = trim($value);

			if ($key === 'Encrypted') {
				if ($value !== 'no') {
					$encrypted = TRUE;
					$copyEncrypted = strpos($value, 'copy:no') === FALSE;
					$changeEncrypted = strpos($value, 'change:no') === FALSE;
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
				$pageFormatA4 = (strpos($value, 'A4') === FALSE);
			}
		}

		// Forms are readable (AcroForm we know for sure, but we expect all forms)
		if ($form !== 'none') {
			$encrypted = FALSE;
		}
		// Version < 1.6 can also be read if copy of change isn't encrypted
		if ($version < 1.6 && (!$copyEncrypted || !$changeEncrypted)) {
			$encrypted = FALSE;
		}

		// PDF version 1.6 is also readable for if not optimized
		if ($version === 1.6 && !$optimized) {
			$encrypted = FALSE;
		}
		// PDF version 1.6 and no A4 value is also (most times) readable
		if ($version === 1.6 && !$pageFormatA4) {
			$encrypted = FALSE;
		}

		return $encrypted;
	}

	/**
	 * Use tika to extract contents of pdf file
	 *
	 * @param File $file
	 * @return string
	 */
	protected function fileToText(File $file) {
		$content = NULL;
		$tikaCommand = CommandUtility::getCommand('java')
			. ' -Dfile.encoding=UTF8' // forces UTF8 output
			. ' -jar ' . escapeshellarg($this->pathTika)
			. ' -t'
			. ' ' . escapeshellarg($file->getForLocalProcessing(FALSE));

		exec($tikaCommand, $output);

		if ($output) {
			$content = implode(PHP_EOL, $output);
		}

		return $content;
	}

	/**
	 * @param $message
	 * @param int $level
	 */
	protected function writeLog($message, $level = 0) {
		/** @var \TYPO3\CMS\Core\Authentication\BackendUserAuthentication $backendUser */
		$backendUser = $GLOBALS['BE_USER'];
		if (isset($backendUser)) {
			$backendUser->writelog(4, 1, $level, 0, '[solrfal_textextract] ' . $message, array());
		}
	}
}
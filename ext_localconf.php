<?php

defined('TYPO3') || die('Access denied.');

if (!isset($GLOBALS['TYPO3_CONF_VARS']['LOG']['BeechIt']['SolrfalTextextract']['writerConfiguration'])) {
    if (\TYPO3\CMS\Core\Core\Environment::getContext()->isProduction()) {
        $logLevel = \TYPO3\CMS\Core\Log\LogLevel::ERROR;
    } elseif (\TYPO3\CMS\Core\Core\Environment::getContext()->isDevelopment()) {
        $logLevel = \TYPO3\CMS\Core\Log\LogLevel::DEBUG;
    } else {
        $logLevel = \TYPO3\CMS\Core\Log\LogLevel::INFO;
    }

    $GLOBALS['TYPO3_CONF_VARS']['LOG']['BeechIt']['SolrfalTextextract']['writerConfiguration'] = [
        $logLevel => [
            \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [
                'logFile' => \TYPO3\CMS\Core\Core\Environment::getVarPath() . '/log/solrfal_textextract.log'
            ]
        ],
    ];
}

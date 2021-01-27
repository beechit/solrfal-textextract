<?php

defined('TYPO3_MODE') || die('Access denied.');

if (TYPO3_MODE === 'BE') {

    /** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
    $signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        'TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher'
    );

    // ext:solrfal enrich metadata and generate correct public url slot
    $signalSlotDispatcher->connect(
        'ApacheSolrForTypo3\\Solrfal\\Indexing\\DocumentFactory',
        'fileMetaDataRetrieved',
        'BeechIt\\SolrfalTextextract\\Aspects\\SolrFalAspect',
        'fileMetaDataRetrieved'
    );
}

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

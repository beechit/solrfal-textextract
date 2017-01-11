<?php

defined('TYPO3_MODE') || die('Access denied.');

if (TYPO3_MODE === 'BE') {

    /** @var \TYPO3\CMS\Extbase\SignalSlot\Dispatcher $signalSlotDispatcher */
    $signalSlotDispatcher = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        'TYPO3\\CMS\\Extbase\\SignalSlot\\Dispatcher'
    );

    // ext:solrfal enrich metadata and generate correct public url slot
    $signalSlotDispatcher->connect(
        'TYPO3\\Solr\\Solrfal\\Indexing\\DocumentFactory',
        'fileMetaDataRetrieved',
        'BeechIt\\SolrfalTextextract\\Aspects\\SolrFalAspect',
        'fileMetaDataRetrieved'
    );
}

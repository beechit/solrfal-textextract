<?php

defined('TYPO3') || die('Access denied.');

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'solrfal_textextract',
    'Configuration/TypoScript',
    'Apache Solr FAL - textextraction'
);

<?php
$EM_CONF[$_EXTKEY] = array(
    'title' => 'Apache Solr for TYPO3 - File Indexing - Text extracting',
    'description' => 'Add text extracting for indexing of FileAbstractionLayer based files in TYPO3 CMS',
    'category' => 'misc',
    'shy' => '',
    'priority' => '',
    'module' => '',
    'state' => 'beta',
    'internal' => '',
    'author' => 'Frans Saris (Beech.it)',
    'author_email' => 't3ext@beech.it',
    'author_company' => 'Beech IT',
    'clearCacheOnLoad' => 1,
    'lockType' => '',
    'version' => '1.1.0',
    'constraints' =>
        array(
            'depends' => array(
                'typo3' => '10.4',
                'solrfal' => '4.0',
            ),
            'conflicts' => array(),
        ),
);

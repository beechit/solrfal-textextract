<?php

$EM_CONF[$_EXTKEY] = [
    'title' => 'Apache Solr for TYPO3 - File Indexing - Text extracting',
    'description' => 'Add text extracting for indexing of FileAbstractionLayer based files in TYPO3 CMS',
    'category' => 'misc',
    'state' => 'beta',
    'author' => 'Frans Saris (Beech.it)',
    'author_email' => 't3ext@beech.it',
    'author_company' => 'Beech IT',
    'version' => '1.1.2',
    'constraints'
        => [
            'depends' => [
                'typo3' => '10.4.0-11.5.99',
                'solrfal' => '4.0.0-11.99.99',
            ],
            'conflicts' => [],
        ],
];

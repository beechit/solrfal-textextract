<?php
$EM_CONF[$_EXTKEY] = array(
	'title' => 'Apache Solr for TYPO3 - File Indexing - Text extracting',
	'description' => 'Add  Text extracting for indexing of FileAbstractionLayer based files in TYPO3 CMS.',
	'category' => 'misc',
	'shy' => '',
	'priority' => '',
	'module' => '',
	'state' => 'beta',
	'internal' => '',
	'author' => 'Frans Saris',
	'author_email' => 't3ext@beech.it',
	'author_company' => 'Beech IT',
	'clearCacheOnLoad' => 1,
	'lockType' => '',
	'version' => '0.0.1',
	'constraints' =>
	array(
		'depends' => array(
			'typo3' => '6.2.7-6.2.99',
			'solr' => '3.0.*-0.0.0',
			'solrfal' => '2.0.1-0.0.0',
		),
		'conflicts' => array(),
	),
);

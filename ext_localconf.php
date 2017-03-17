<?php

if (!defined ('TYPO3_MODE')) die ('Access denied.');

use PAGEmachine\Searchable\Command\IndexCommandController;
if (TYPO3_MODE === 'BE') {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers']['searchable'] = IndexCommandController::class;
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'PAGEmachine.' . $_EXTKEY,
    'Search',
    ['Search' => 'search, result'],
    ['Search' => 'search, result']
);

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable'] = [
    // The fieldname to store meta information in (link, preview etc.). This field will be added to all created ES types and set to index = false
    // Note that this field will also affect how you can access the meta fields in templates!
    'metaField' => 'searchable_meta',
    //Add indices here. Default format: languagekey => indexname
    'indices' => [],
    //Add your indexer configurations here. Each indexer represents a toplevel object type like news, pages etc.
    'indexers' => []
];

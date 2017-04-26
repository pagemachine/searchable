<?php

if (!defined ('TYPO3_MODE')) die ('Access denied.');

if (TYPO3_MODE === 'BE') {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers']['searchable'] = \PAGEmachine\Searchable\Command\SearchableCommandController::class;
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'PAGEmachine.' . $_EXTKEY,
    'Searchbar',
    ['Search' => 'searchbar'],
    ['Search' => 'searchbar']
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'PAGEmachine.' . $_EXTKEY,
    'Results',
    ['Search' => 'results'],
    ['Search' => 'results']
);


$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_db.php']['queryProcessors']['searchable'] = \PAGEmachine\Searchable\Hook\DatabaseConnectionHook::class;

//Add custom logger
$GLOBALS['TYPO3_CONF_VARS']['LOG']['PAGEmachine']['Searchable']['Query']['writerConfiguration'] = array(
    // configuration for ERROR level log entries
  \TYPO3\CMS\Core\Log\LogLevel::ERROR => array(
      // add a FileWriter
    'TYPO3\\CMS\\Core\\Log\\Writer\\FileWriter' => array(
        // configuration for the writer
      'logFile' => 'typo3temp/logs/searchable.log'
    )
  )
);


$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable'] = [
    // The fieldname to store meta information in (link, preview etc.). This field will be added to all created ES types and set to index = false
    // Note that this field will also affect how you can access the meta fields in templates!
    'metaField' => 'searchable_meta',
    //Update index. Used for storing the records to update in the next indexing run
    'updateIndex' => [
        'name' => 'searchable_updates'
    ],
    //Add indices here. Default format: languagekey => indexname
    'indices' => [],
    //Add your indexer configurations here. Each indexer represents a toplevel object type like news, pages etc.
    'indexers' => []
];

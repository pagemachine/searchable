<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'PAGEmachine.Searchable',
    'Searchbar',
    ['Search' => 'searchbar'],
    ['Search' => 'searchbar']
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'PAGEmachine.Searchable',
    'LiveSearchbar',
    ['Search' => 'liveSearchbar'],
    ['Search' => 'liveSearchbar']
);


\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'PAGEmachine.Searchable',
    'Results',
    ['Search' => 'results'],
    ['Search' => 'results']
);

// Add custom logging
if (empty($GLOBALS['TYPO3_CONF_VARS']['LOG']['PAGEmachine']['Searchable']['writerConfiguration'])) {
    $GLOBALS['TYPO3_CONF_VARS']['LOG']['PAGEmachine']['Searchable']['writerConfiguration'] = [
        \TYPO3\CMS\Core\Log\LogLevel::ERROR => [
            \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [
                'logFile' => 'typo3temp/logs/searchable.log',
            ],
        ],
    ];
}

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable'] = [
    // Configuration coming from Extension Manager
    // Subkey 'hosts' contains connection credentials
    // See https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/_configuration.html#_extended_host_configuration for available options
    'extensionManagement' => [
        'connection' => [
            'hosts' => 'http://localhost:9200',
        ],
        'indexing' => [
            'domain' => 'http://localhost:80',
        ],
    ],
    // The fieldname to store meta information in (link, preview etc.). This field will be added to all created ES types and set to index = false
    // Note that this field will also affect how you can access the meta fields in templates!
    'metaField' => 'searchable_meta',
    //Update index. Used for storing the records to update in the next indexing run
    'updateIndex' => [
        'name' => 'searchable_updates',
    ],
    //Add indices here. Default format: language UID => index configuration
    'indices' => [],
    //Add your indexer configurations here. Each indexer represents a toplevel object type like news, pages etc.
    'indexers' => [],
    //Define pipelines here. Pipelines can be used to modify content during indexing
    'pipelines' => [],
    //Default index settings used for every index. If you define custom settings, these will be merged with them
    'defaultIndexSettings' => [
        'number_of_shards' => 1,
        'number_of_replicas' => 0,
    ],
    'query' => [
        PAGEmachine\Searchable\Query\SearchQuery::class => [
            'features' => [
                'highlighting' => [
                    'className' => PAGEmachine\Searchable\Feature\HighlightFeature::class,
                ],
                'termSuggest' => [
                    'className' => PAGEmachine\Searchable\Feature\TermSuggestFeature::class,
                ],
            ],
        ],
        PAGEmachine\Searchable\Query\AutosuggestQuery::class => [
            'features' => [
                'completionSuggest' => [
                    'className' => PAGEmachine\Searchable\Feature\CompletionSuggestFeature::class,
                ],
            ],
        ],
    ],
];

// Load Extension Manager settings
(function (): void {
    $extensionConfiguration = [];

    if (class_exists(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class)) {
        try {
            $extensionConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Configuration\ExtensionConfiguration::class)
                ->get('searchable');
        } catch (\TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException $e) {
            $extensionConfiguration = [];
        }
    } else {
        $extensionConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\TypoScript\TypoScriptService::class)
            ->convertTypoScriptArrayToPlainArray(unserialize($_EXTCONF));
    }

    foreach ($extensionConfiguration as $key => $value) {
        if (is_array($value) && isset($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['extensionManagement'][$key])) {
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['extensionManagement'][$key] = array_merge(
                $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['extensionManagement'][$key],
                $extensionConfiguration[$key]
            );
        } else {
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['extensionManagement'][$key] = $extensionConfiguration[$key];
        }
    }
})();

//Register eid
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['searchable_autosuggest'] = \PAGEmachine\Searchable\Eid\Autosuggest::class . '::processRequest';
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['searchable_search'] = \PAGEmachine\Searchable\Eid\Search::class . '::processRequest';

// Register Hook for dynamic Plugin FlexForms
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][\TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools::class]['flexParsing']['searchable'] =
        \PAGEmachine\Searchable\Hook\DynamicFlexFormHook::class;

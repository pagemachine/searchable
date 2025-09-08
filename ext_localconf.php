<?php

use PAGEmachine\Searchable\Controller\SearchController;
use PAGEmachine\Searchable\Eid\Autosuggest;
use PAGEmachine\Searchable\Eid\Search;
use PAGEmachine\Searchable\Feature\CompletionSuggestFeature;
use PAGEmachine\Searchable\Feature\HighlightFeature;
use PAGEmachine\Searchable\Feature\TermSuggestFeature;
use PAGEmachine\Searchable\Hook\DynamicFlexFormHook;
use PAGEmachine\Searchable\Query\AutosuggestQuery;
use PAGEmachine\Searchable\Query\SearchQuery;
use TYPO3\CMS\Core\Configuration\Exception\ExtensionConfigurationExtensionNotConfiguredException;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\Writer\FileWriter;
use TYPO3\CMS\Core\Log\Writer\PhpErrorLogWriter;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

if (!defined('TYPO3')) {
    die('Access denied.');
}

ExtensionUtility::configurePlugin(
    'Searchable',
    'Searchbar',
    [
        SearchController::class => 'searchbar',
    ],
    [
        SearchController::class => 'searchbar',
    ]
);

ExtensionUtility::configurePlugin(
    'Searchable',
    'LiveSearchbar',
    [
        SearchController::class => 'liveSearchbar',
    ]
);


ExtensionUtility::configurePlugin(
    'Searchable',
    'Results',
    [
        SearchController::class => 'results',
    ],
    [
        SearchController::class => 'results',
    ]
);

// Add custom logging
if (empty($GLOBALS['TYPO3_CONF_VARS']['LOG']['PAGEmachine']['Searchable']['writerConfiguration'])) {
    $GLOBALS['TYPO3_CONF_VARS']['LOG']['PAGEmachine']['Searchable']['writerConfiguration'] = [
        LogLevel::ERROR => [
            FileWriter::class => [
                'logFile' => 'typo3temp/logs/searchable.log',
            ],
        ],
    ];
}

if (Environment::isCli()) {
    $GLOBALS['TYPO3_CONF_VARS']['LOG']['PAGEmachine']['Searchable']['writerConfiguration'][LogLevel::INFO][PhpErrorLogWriter::class] = [];
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
        'number_of_shards' => 2,
        'number_of_replicas' => 0,
    ],
    'defaultMapping' => [
        'features' => [
            'suggest' => [
                'className' => TermSuggestFeature::class,
            ],
        ],
    ],
    'query' => [
        SearchQuery::class => [
            'features' => [
                'highlighting' => [
                    'className' => HighlightFeature::class,
                ],
                'termSuggest' => [
                    'className' => TermSuggestFeature::class,
                ],
            ],
        ],
        AutosuggestQuery::class => [
            'features' => [
                'completionSuggest' => [
                    'className' => CompletionSuggestFeature::class,
                ],
            ],
        ],
    ],
];

// Load Extension Manager settings
(function (): void {
    try {
        $extensionConfiguration = GeneralUtility::makeInstance(
            ExtensionConfiguration::class
        )->get('searchable');
    } catch (ExtensionConfigurationExtensionNotConfiguredException) {
        $extensionConfiguration = [];
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
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['searchable_autosuggest'] = Autosuggest::class . '::processRequest';
$GLOBALS['TYPO3_CONF_VARS']['FE']['eID_include']['searchable_search'] = Search::class . '::processRequest';

// Register Hook for dynamic Plugin FlexForms
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][FlexFormTools::class]['flexParsing']['searchable'] =
        DynamicFlexFormHook::class;

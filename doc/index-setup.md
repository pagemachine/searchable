# Index Setup

Searchable follows the approach to use one Elasticsearch index per language and indexer combination. But in the configuartion it is only necessary to define an index per typo3 language. Each index in turn then defines which indexer should run.

```php
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['indices'] = [
  'english' => [
    'typo3_language' => 0, // sys_language_uid
    'indexer' => ['pages'], // Array of indexers to run. If not defined all indexer will be executed
    'settings' => [
        // Your index settings
    ]
  ],
  'german' => [
    'typo3_language' => 1, // sys_language_uid
    'indexer' => ['pages'], // Array of indexers to run. If not defined all indexer will be executed
    'settings' => [
        // Your index settings
    ]
  ],
];
```

By default the settings will be merged with the default settings(`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['indices']['defaultIndexSettings]`).

You can find a list of available settings in the [Index Modules](https://www.elastic.co/guide/en/elasticsearch/reference/current/index-modules.html) section of the official ES documentation. Some common examples will be explained here.

## Environment settings

Each index can have specific environment settings which are applied before each index is updated. The following environment settings are supported:

* `language`: a two-lettered language ISO code as required for resource translations (`locallang.xlf`) in TYPO3, e.g. `de` or `ja`
* `locale`: an identifier of an installed system locale, e.g. `de_DE.utf-8` or `ja_JP.utf-8`

Setting these is essential e.g. for proper translations in preview rendering.

This is how the index configuration could look like to set up the environment for a German index:

```php
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['indices'] = [
  'german' => [
    'typo3_language' => 1, // sys_language_uid
    'indexer' => ['pages'], // Array of indexers to run. If not defined all indexer will be executed
    'environment' => [
      'language' => 'de',
      'locale' => 'de_DE.utf-8',
    ],
    'settings' => [
      // ...
    ]
  ],
];
```

## Setting default Analyzers

[Analyzers](https://www.elastic.co/guide/en/elasticsearch/reference/current/analysis.html) are very important to provide more intelligent search results, f.ex. by taking the current language into account. A recommended analysis setting for a 2-language-setup could look like this:
```php
//Analyzer setup for english index
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['indices']['english']['settings']['analysis'] = [
  'analyzer' => [
    'default' => ['type' => 'english'],
    'default_search' => ['type' => 'english']
  ]
];

//Analyzer setup for german index
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['indices']['german']['settings']['analysis'] = [
  'analyzer' => [
    'default' => ['type' => 'german'],
    'default_search' => ['type' => 'german']
  ]
];
```

## Apply setup

After setting up all indices and types run the following CLI command to apply the setup:

    typo3cms searchable:setup

Whenever the configuration was changed, this command must be run again. It can be run at any time, since it only performs the necessary changes.

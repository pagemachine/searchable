# Index Setup

Searchable under the hood uses one Elasticsearch index per language and indexer combination. But in configuartion it is only necessary to define one index per typo3 language. Each index in turn then defines which indexers should run.

```php
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['indices'] = [
  'english' => [
    'typo3_language' => 0, // sys_language_uid
    'indexer' => ['pages'], // Array of indexers to run. If not defined, all indexers are executed
    'settings' => [
        // Your index settings
    ]
  ],
  'german' => [
    'typo3_language' => 1, // sys_language_uid
    'indexer' => ['pages'], // Array of indexers to run. If not defined, all indexers are executed
    'settings' => [
        // Your index settings
    ]
  ],
];
```

By default the settings will be merged with the default settings(`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['indices']['defaultIndexSettings]`).

You can find a list of available settings in the [Index Modules](https://www.elastic.co/guide/en/elasticsearch/reference/current/index-modules.html) section of the official ES documentation. But we will cover some common settings in the following sections.

## Environment settings

Each index can have specific environment settings which are applied before each index is updated. The following environment settings are supported:

* `language`: a two-letter ISO language code required for TYPO3 resource translations (`locallang.xlf`), e.g. `de` or `ja`
* `locale`: an identifier of an installed system locale, e.g. `de_DE.utf-8` or `ja_JP.utf-8`

Setting these is essential e.g. for proper translations in preview rendering.

For example, if you have a German index, you should set the environment settings like this:

```php
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['indices'] = [
  'german' => [
    'typo3_language' => 1, // sys_language_uid
    'indexer' => ['pages'], // Array of indexers to run. If not defined, all indexers are executed
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

[Analyzers](https://www.elastic.co/guide/en/elasticsearch/reference/current/analysis.html) are important to produce better search results, as they allow the search engine to take the current language into account. For example the settings for a two-language setup could look like this:

```php
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['indices'] = [
    'english' => [
        // ...
        'settings' => [
            'analysis' => [
                'analyzer' => [
                    'default' => ['type' => 'english'],
                    'default_search' => ['type' => 'english']
                ]
            ]
        ]
        // ...
    ],
    'german' => [
        // ...
        'settings' => [
            'analysis' => [
                'analyzer' => [
                    'default' => ['type' => 'german'],
                    'default_search' => ['type' => 'german']
                ]
            ]
        ]
        // ...
    ],
]
```

> [!NOTE]
> For languages with compound words (like German) it is recommended to set up an [Hyphenation Decompounder](https://www.elastic.co/search-labs/blog/compound-word-search) in Elasticsearch. As it dramatically improves search results.

# Full indexer configuration
Combining all the above, a full configuration for a two-language setup could look like this:

```php
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['indices'] = [
    'english' => [
        'typo3_language' => 0, // sys_language_uid
        'indexer' => ['pages'], // Array of indexers to run. If not defined, all indexers are executed
        'environment' => [
            'language' => 'en',
            'locale' => 'en_US.utf-8',
        ],
        'settings' => [
            'analysis' => [
                'analyzer' => [
                    'default' => ['type' => 'english'],
                    'default_search' => ['type' => 'english']
                ]
            ]
        ]
    ],
    'german' => [
        'typo3_language' => 1, // sys_language_uid
        'indexer' => ['pages'], // Array of indexers to run. If not defined, all indexers are executed
        'environment' => [
            'language' => 'de',
            'locale' => 'de_DE.utf-8',
        ],
        'settings' => [
            'analysis' => [
                'analyzer' => [
                    'default' => ['type' => 'german'],
                    'default_search' => ['type' => 'german']
                ]
            ]
        ],
    ],
]
```

## Apply setup

After setting up all indices and types, run the following CLI command to apply the setup:

    typo3 index:setup

Whenever the configuration changes, run this command again. It can be run at any time because it only performs the necessary changes.

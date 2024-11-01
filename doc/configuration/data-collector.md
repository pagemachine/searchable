# DataCollector configuration settings

DataCollectors handle fetching and conversion of (database) data.

## General settings

| Option | Type | Required | Values | Description |
|---------|------------|----------|-----------|--------------|
| table | string | yes |  | The name of the table to fetch. The DataCollector will use the TCA array associated with this table name to load data. |
| pid | integer | no | Default: *null* | Pid restriction. If the pid setting is *null*, all records from a given table are loaded. |
| mode | string | yes | *whitelist*/*blacklist*, default: *whitelist* | Used in combination with the *fields* array. |
| fields | array | no | Default: *empty* | Determines which fields to add to the index. If mode is set to *whitelist*, only the given fields are loaded. In *blacklist* mode, all fields that are **not** in the array are loaded. |
| sysLanguageOverlay | int/string | yes | 0/1/*hideNonTranslated*, default: 1 | Determines whether to use sysLanguageOverlay for translated records. See the [TYO3 Documentation](https://docs.typo3.org/typo3cms/TyposcriptReference/Setup/Config/Index.html#sys-language-overlay) for details about this setting. Usually this should have the same value as your TypoScript setting (`config.sys_language_overlay`). |
| subCollectors | array | no | Default: *empty* | Define SubCollectors for relations here. Just like the toplevel collector config, each subCollector is an array containing two keys: `className` (where you add the collector class) and `config` (where you can use all settings listed here, even sub-subCollectors). The array key defines the field name in the elasticsearch index. Note that the *table* setting is not needed in subCollectors since the child table is defined via TCA. However, you need to tell searchable which field in the parent table represents the relation (see below).  |
| field (SubCollectors only) | string | yes |  | The field in the parent table that holds the relation to the child record. This is required since the TCA of the corresponding column tells the collector important information about the relation. |
| select.additionalWhereClauses | array(string) | no | *empty* | Additional where-clauses to filter out content you don't want to index. Each clause should start with " AND ". |
| select.additionalTables | array(string) | no | *empty* | Additional tables in the select query (FROM). Can be used in addition to *additionalWhereClauses* (for example when filtering by relations). |

### Example

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['indexers']['your-custom-indexer'] = [
        'className' => \PAGEmachine\Searchable\Indexer\TcaIndexer::class,
        'config' => [
            'collector' => [
                'className' => \PAGEmachine\Searchable\DataCollector\TcaDataCollector::class,
                'config' => [
                    'table' => 'tx_example_domain_model_example',
                    'pid' => 123,
                    'mode' => 'whitelist',
                    'fields' => [
                        'title',
                        'content'
                    ],
                    'subCollectors' => [
                        'myRelation' => [
                            'className' => \PAGEmachine\Searchable\DataCollector\TcaDataCollector::class,
                            'config' => [
                                'field' => 'relationFieldInTCA',
                                ...
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ];

## PagesDataCollector settings

These settings are specific for the PagesDataCollector. The PagesDataCollector holds default values for all settings mentioned above (f.ex. you do not need to declare the table, we know where to find *pages* in the database ;) ). However, there are some special settings listed below:

| Option | Type | Required | Values | Description |
|----------|---------|----------|--------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| pid | integer | yes | Default: *0* | The pid to start the recursive fetching from. Since the PagesDataCollector fetches pages in a recursive tree structure, you should set the pid setting to your root page. |
| doktypes | string | yes | Default: *1* | Which doktypes to index. Default is 1 (normal pages). |
| transientDoktypes | string | yes | Default: *3,4,199* | Doktypes which should not be included in the index, but should be searched for valid subpages. Default is 3 (links), 4 (shortcuts) and 199 (spacers). |
| groupWhereClause | string | yes | Default: *AND (pages.fe\_group = "" OR pages.fe\_group = 0)* | The where clause applied for fe_group restrictions. By default all pages with access settings are hidden. You can add *" OR pages.fe\_group = -1"* if you want to show pages which are visible for non-authenticated users. |
| includeHideInMenu | boolean | yes | Default: *false* | Whether to include pages that are hidden in menu (`nav\_hide` field). |

### Example

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['indexers']['pages'] = [
        'className' => \PAGEmachine\Searchable\Indexer\PagesIndexer::class,
        'config' => [
            'collector' => [
                'config' => [
                    'pid' => 1
                ]
            ]
        ]
    ];

## FileDataCollector settings


| Option | Type | Required | Values | Description |
|----------|---------|----------|--------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| mimetypes | array (string) | yes | Default: *text/plain, application/pdf* | Filter indexed files by mimetype. Customize if you know what you're doing (or what the elasticsearch attachment plugin can handle). |

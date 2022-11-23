# Language Handling

## Language GET parameter

Since the extension does work in backend/command context, it does not know in which GET parameter language is stored.

This is why LinkBuilders come with their own setting for that (default is `L`).

You can change the parameter inside the LinkBuilder config (needs to be set for each indexer you build):

```php
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['indexers']['your-indexer'] = [
    'className' => \Your\Indexer::class,
    'config' => [
        //...
        'link' => [
            'config' => [
                'languageParam' => 'L', //Switch to whatever parameter you use
            ],
        ],
    ],
];
```

## Overlay behaviour

Language Handling in TYPO3 is quite complicate and (in case of Extbase) also inconsistent.

Searchable is meant to support Extbase extensions in the best way possible, that is why `sys_language_overlay` is set to 1 by default for the indexing process (pretty much how Extbase behaves).

If `sys_language_overlay` is set to a different value than 1 in your environment, you can force searchable to use a different value by setting it in the DataCollector config for the affected record type:
```php
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['indexers']['your_type']['config']['collector']['config']['sysLanguageOverlay'] = 0|1|'hideNonTranslated';
```

The different language modes for pages (`config.sys_language_mode`) are currently not supported.

# Language Handling

Language Handling in TYPO3 is quite complicate and (in case of Extbase) also inconsistent.

Searchable is meant to support Extbase extensions in the best way possible, that is why `sys_language_overlay` is set to 1 by default for the indexing process (pretty much how Extbase behaves). 

If `sys_language_overlay` is set to a different value than 1 in your environment, you can force searchable to use a different value by setting it in the DataCollector config for the affected record type:

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['indexers']['your_type']['config']['collector']['config']['sysLanguageOverlay'] = 0|1|'hideNonTranslated'

The different language modes for pages (`config.sys_language_mode`) are currently not supported.

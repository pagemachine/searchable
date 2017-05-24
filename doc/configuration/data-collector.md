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
| subCollectors | array | no | Default: *empty* | Define SubCollectors for relations here. Just like the toplevel collector config, each subCollector is an array containing two keys: `className` (where you add the collector class) and `config` (where you can use all settings listed here, even sub-subCollectors). Note that the *table* setting is not needed in subCollectors since the child table is defined via TCA. However, you need to tell searchable which field in the parent table represents the relation (see below).  |
| field (SubCollectors only) | string | yes |  | The field in the parent table that holds the relation to the child record. This is required since the TCA of the corresponding column tells the collector important information about the relation. |

## PagesDataCollector settings

These settings are specific for the PagesDataCollector. The PagesDataCollector holds default values for all settings mentioned above (f.ex. you do not need to declare the table, we know where to find *pages* in the database ;) ). However, there are some special settings listed below:

| Option | Type | Required | Values | Description |
|----------|---------|----------|--------------|---------------------------------------------------------------------------------------------------------------------------------------------------------------------------|
| pid | integer | yes | Default: *0* | The pid to start the recursive fetching from. Since the PagesDataCollector fetches pages in a recursive tree structure, you should set the pid setting to your root page. |
| doktypes | string | yes | Default: *1,4* | Which doktypes to index. Default is 1 (normal pages) and 4 (shortcuts). |
|  |  |  |  |  |

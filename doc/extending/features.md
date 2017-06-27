# Using Feature Classes

Features are a very easy solution to modify mappings, content and queries all in one central place.
Basically they work like predefined classes addressing 3 hook-like functions:

- `modifyMapping()` to add custom mapping
- `modifyRecord()` to add or manipulate fields in each indexed record
- `modifyQuery()` to manipulate a query before it is sent to Elasticsearch

However, they don't have to use each function. It depends on where you register them if they are called or not.

Feature classes can also hold their own configuration for extra convenience.

A good example for a feature using all three hooks is `PAGEmachine\Searchable\Feature\ResultHighlightFeature`, a feature to return highlighted snippets containing the search word for each content.

## Modifying mappings

The first function is `modifyMapping` which - as the name suggests - is called before the mapping for one type is saved.

To register your class for this function, you have to add it to the **Indexer** features (toplevel). Example from the *highlight* feature:

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['indexers']['pages']['config']['features']['highlighting'] = [
        'className' => \PAGEmachine\Searchable\Feature\ResultHighlightFeature::class,
        'config' => [
            // Here you can override the default config of this feature, if necessary
        ]
    ];

Now each time this particular indexer builds its mapping, your feature is called and you can append whatever mapping you need.

## Modifying records

Records can be modified with the `modifyRecord` function. Each **DataCollector** calls this function just before it hands the current record back to the Indexer.
Therefore you need to register your feature on **DataCollector** level. Note that you also have to register it separately for each subcollector!

## Modifying queries

Query modifying consists of several steps, as it concerns the query itself as well as templates and TypoScript/plugin settings.

First, register your feature for a query class (`SearchQuery` most likely, as this is the default query):

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['query'][PAGEmachine\Searchable\Query\SearchQuery::class]['features']['highlighting'] = [
        'className' => PAGEmachine\Searchable\Feature\ResultHighlightFeature::class,
        'config' => [
            // Here you can override the default config of this feature, if necessary
        ]        
    ];

To make the feature controllable via plugin and enable it in TS, you have to add it to the feature settings in TypoScript. You can either add it to the **Searchbar** or the **Results**
plugin, depending on where you register it.
For the search plugin, add this to your `ext_typoscript_setup.txt`:

    plugin.tx_searchable_searchbar.settings.features {
        highlighting = 1
    }

For the results plugin add this:

    plugin.tx_searchable_results.settings.features {
        highlighting = 1
    }

**Important:** The entered key (here *highlighting*) must match the `featureName` property of your feature class.

Now the feature is activated and you can toggle it for each plugin individually.

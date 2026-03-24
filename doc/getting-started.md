# Getting started

Searchable uses **[Elasticsearch](https://www.elastic.co/)** via **[elasticsearch-php](https://packagist.org/packages/elasticsearch/elasticsearch)** to index and search content.

While `elasticsearch-php` is included via Composer, you must provide a running Elasticsearch instance yourself.


## Installation
Install Searchable via *Composer*:

    composer require pagemachine/searchable

Now simply install it via Extension Manager or TYPO3 console.

## Setup
* Add the Elasticsearch connection data and the host name of your site via Extension Manager Settings (See: [EM Settings](configuration/em-settings.md))
* Include **TypoScript** and **Constants** in your template (static includes)
* Create a page with the *Search* plugin
* Configure your **indices** and **indexers** (see the next chapter)
* Run the `typo3 index:setup` command to create the configured indices
* Run the `typo3 index:update:full` command to run all defined indexers

## Example Configuration

To create a simple setup for a monolingual page, first configure a **default index**:
```php
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['indices'] = [
  'english' => [
    'typo3_language' => 0,
  ],
];
```
Now we need to define the **indexers** we want to run.
Usually everything that produces a single search result URL (*pages* and top-level extension content such as *news*) deserves a separate indexer. Records that "belong" to another record, such as *tt_content*, *categories*, and *tags* are nested as **subtypes** in the top-level indexer configuration.

Let's create a simple setup for page indexing:
```php
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['indexers']['pages'] = [
    'indexer' => \PAGEmachine\Searchable\Indexer\PagesIndexer::class
];
```

That's it. This simply defines a new type (*"pages"*) and tells searchable to use the predefined `PagesIndexer` class for indexing.
For extension content you need to define some more details, but the `PagesIndexer` contains a lot of default configuration to simplify the job.

## Site support

By default indexing will use the first site (as returned by the `SiteFinder`) which may lead to unexpected results, e.g. when using Fluid for preview rendering and Typolink.

A specific site can be configured for each indexer using the `siteIdentifier` configuration option:

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['indexers']['pages'] = [
        'indexer' => \PAGEmachine\Searchable\Indexer\PagesIndexer::class,
        'config' => [
            'siteIdentifier' => 'foo',
        ],
    ];

Also there should be a language in the site with an `languageId` matching each index language, otherwise the default site language will be used as fallback.

## Next steps

Once you have completed the basic setup above, proceed with:

* **[Index Setup](index-setup.md)** — Learn how to configure indices for multiple languages and apply custom settings
* **[Index Updating](index-update.md)** — Understand how to keep your indices up to date
* **[File Indexing](indexing-files.md)** — Set up indexing for common file formats like PDFs

Back to [home](index.md).

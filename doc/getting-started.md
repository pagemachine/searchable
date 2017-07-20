# Getting started

Searchable uses **[Elasticsearch](https://www.elastic.co/)** via **[elasticsearch-php](https://packagist.org/packages/elasticsearch/elasticsearch)** to index and search content.

While elasticsearch-php is included via composer, you must take care of a running elasticsearch instance yourself.


## Installation
To install searchable, simply require it via *composer* (command line):

    composer require pagemachine/searchable:dev-master

Make sure you have added the bitbucket repository to your `composer.json`:

    "repositories": {
        "searchable": {
            "type": "vcs",
            "url": "git@bitbucket.org:pagemachineag/searchable.git"
        }
    },

Now simply install it via Extension Manager or TYPO3 console.

## Setup
* Add the elasticsearch connection data and the host name of your site via Extension Manager Settings (See: [EM Settings](configuration/em-settings.md))
* Include **Typoscript** and **Constants** into your template (static includes)
* Create a search page and and *Search* Plugin element inside
* Configure your **indices** and **indexers** (see the next chapter)
* Run the command controller to create the configured indices (Command TBA)
* Run `[webroot]/typo3/cli_dispatch.phpsh index:indexFull` (runs all defined Indexers)


## Example Configuration

To create a simple setup for a non-multilanguage page, first configure a **default index**:

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['indices'] = [
      '0' => [
        'name' => 'typo3'
      ]
    ];

Now we need to define the **indexers** we want to run.
Usually everything that produces a single search result URL (*pages* and toplevel extension content such as *news*) deserves a separate indexer. Records that "belong" to another record, such as *tt_content*, *categories*, *tags*, will be appended as **subtypes** in the toplevel indexer configuration.

Let's create a simple setup for page indexing:

    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['indexers']['pages'] = [
        'indexer' => \PAGEmachine\Searchable\Indexer\PagesIndexer::class
    ];

That's it. This simply defines a new type (*"pages"*) and tells searchable to use the predefined `PagesIndexer` class for indexing.
For extension content you need to define some more details, but the `PagesIndexer` contains a lot of default configuration to simplify the job.

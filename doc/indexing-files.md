# File Indexing

Elasticsearch can index file content via ingest attachment plugin.

You need to install the plugin (see [here](https://www.elastic.co/guide/en/elasticsearch/plugins/current/ingest-attachment.html)) and also create a pipeline configuration in your `ext_localconf.php`:


    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['pipelines'] = [
        'attachment' => [
            'description' => 'Extract attachment information from arrays',
            'processors' => [
                [
                    'foreach' => [
                        'field' => 'attachments',
                        'processor' => [
                            'attachment' => [
                                'target_field' => '_ingest._value.attachment',
                                'field' => '_ingest._value.data',
                                'indexed_chars' => -1
                            ]
                        ]
                    ]
                ],
                [
                    'foreach' => [
                        'field' => 'attachments',
                        'processor' => [
                            'remove' => [ 'field' => '_ingest._value.data']
                        ]
                    ]
                ]
            ]
        ]
    ];

The pipeline itself is created within the `searchable:setup` command, so run it after configuring.

This pipeline does two things:

 * Extract binary file data from the *data* field to the *attachments* field (first foreach processor)

 * Remove the original binary to save disk space (second foreach processor)

Now you can set up the file indexer configuration:

    // Define indexer for files
    $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['indexers']['files'] = [
        'className' => \PAGEmachine\Searchable\Indexer\FileIndexer::class,
        'config' => [
            'type' => 'files',
            'collector' => [
                'config' => [
                    'fields' => [
                        'title',
                        'description',
                        'file'
                    ],
                ]
            ],
            //...
        ]
    ];

By default the `FileLinkBuilder` is already set and good to go, so you just need to define preview rendering and features like as usual.

<?php
namespace PAGEmachine\Searchable\Indexer;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

class PagesIndexer extends Indexer {

    /**
     * @var array
     */
    protected static $defaultConfiguration = [
        'type' => 'pages',
        'collector' => [
            'className' => \PAGEmachine\Searchable\DataCollector\PagesDataCollector::class
        ],
        'link' => [
            'className' => \PAGEmachine\Searchable\LinkBuilder\PageLinkBuilder::class,
            'config' => [
                'titleField' => 'title', 
                'dynamicParts' => [
                    'pageUid' => 'uid'
                ]
            ]
        ],
        'preview' => [
            'className' => \PAGEmachine\Searchable\Preview\FluidPreviewRenderer::class,
            'config' => [
                'templateName' => 'Preview/Pages',
                'fields' => ['content']
            ]
        ],
        'mapping' => [
            '_all' => [
                'store' => true
            ],
            'properties' => [
                'content' => [
                    'properties' => [
                        'header' => [
                            'type' => 'text'
                        ],
                        'bodytext' => [
                            'type' => 'text'
                        ]
                    ]
                ]
            ]
        ]
    ];

}

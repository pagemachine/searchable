<?php
namespace PAGEmachine\Searchable\Indexer;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

class PagesIndexer extends Indexer
{
    /**
     * @var array
     */
    protected static $defaultConfiguration = [
        'type' => 'pages',
        'collector' => [
            'className' => \PAGEmachine\Searchable\DataCollector\PagesDataCollector::class,
        ],
        'link' => [
            'className' => \PAGEmachine\Searchable\LinkBuilder\PageLinkBuilder::class,
            'config' => [
                'titleField' => 'title',
                'dynamicParts' => [
                    'pageUid' => 'uid',
                ],
            ],
        ],
        'preview' => [
            'className' => \PAGEmachine\Searchable\Preview\FluidPreviewRenderer::class,
            'config' => [
                'templateName' => 'Preview/Pages',
                'fields' => ['content'],
            ],
        ],
        'mapper' => [
            'className' => \PAGEmachine\Searchable\Mapper\DefaultMapper::class,
        ],
        'features' => [
            'highlighting' => [
                'className' => \PAGEmachine\Searchable\Feature\HighlightFeature::class,
                'config' => [
                    'fields' => [
                        'content' => [
                            'subheader',
                            'bodytext',
                        ],

                    ],
                ],
            ],
            'completion' => [
                'className' => \PAGEmachine\Searchable\Feature\CompletionSuggestFeature::class,
            ],
        ],
        'mapping' => [
                    'properties' => [
                        'header' => [
                            'type' => 'text',
                        ],
                        'subheader' => [
                            'type' => 'text',
                        ],
                        'bodytext' => [
                            'type' => 'text',
                        ],
                    ],
        ],
    ];
}

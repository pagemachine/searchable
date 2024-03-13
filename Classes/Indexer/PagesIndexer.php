<?php
namespace PAGEmachine\Searchable\Indexer;

use PAGEmachine\Searchable\DataCollector\PagesDataCollector;
use PAGEmachine\Searchable\LinkBuilder\PageLinkBuilder;
use PAGEmachine\Searchable\Preview\FluidPreviewRenderer;
use PAGEmachine\Searchable\Mapper\DefaultMapper;
use PAGEmachine\Searchable\Feature\HighlightFeature;
use PAGEmachine\Searchable\Feature\CompletionSuggestFeature;
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
            'className' => PagesDataCollector::class,
        ],
        'link' => [
            'className' => PageLinkBuilder::class,
            'config' => [
                'titleField' => 'title',
                'dynamicParts' => [
                    'pageUid' => 'uid',
                ],
            ],
        ],
        'preview' => [
            'className' => FluidPreviewRenderer::class,
            'config' => [
                'templateName' => 'Preview/Pages',
                'fields' => ['content'],
            ],
        ],
        'mapper' => [
            'className' => DefaultMapper::class,
        ],
        'features' => [
            'highlighting' => [
                'className' => HighlightFeature::class,
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
                'className' => CompletionSuggestFeature::class,
            ],
        ],
        'mapping' => [
            '_all' => [
                'store' => true,
            ],
            'properties' => [
                'content' => [
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
            ],
        ],
    ];
}

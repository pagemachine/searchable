<?php
namespace PAGEmachine\Searchable\Indexer;

use PAGEmachine\Searchable\DataCollector\PagesDataCollector;
use PAGEmachine\Searchable\Feature\CompletionSuggestFeature;
use PAGEmachine\Searchable\Feature\HighlightFeature;
use PAGEmachine\Searchable\Feature\TermSuggestFeature;
use PAGEmachine\Searchable\LinkBuilder\PageLinkBuilder;
use PAGEmachine\Searchable\Mapper\DefaultMapper;
use PAGEmachine\Searchable\Preview\FluidPreviewRenderer;

/*
 * This file is part of the PAGEmachine Searchable project.
 */
class PagesIndexer extends Indexer
{
    /**
     * @var array
     */
    protected static $defaultConfiguration = [
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
                        'title',
                        'content' => [
                            'header',
                            'subheader',
                            'bodytext',
                        ],

                    ],
                ],
            ],
            'suggest' => [
                'className' => TermSuggestFeature::class,
                'config' => [
                    'fields' => [
                        'title',
                        'content' => [
                            'header',
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

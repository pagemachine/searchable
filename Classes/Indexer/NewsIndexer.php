<?php

namespace PAGEmachine\Searchable\Indexer;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

class NewsIndexer extends Indexer
{
    /**
     * @var array
     */
    protected static $defaultConfiguration = [
        'collector' => [
            'className' => \PAGEmachine\Searchable\DataCollector\TcaDataCollector::class,
            'config' => [
                'table' => 'tx_news_domain_model_news',
                'pid' => null,
                'fields' => [
                    'title',
                    'teaser',
                    'bodytext',
                    'datetime',
                    'hidden',
                    'internalurl',
                    'externalurl',
                    'type',
                    'fal_media',
                ],
                'features' => [
                    'completion' => [
                        'className' => \PAGEmachine\Searchable\Feature\CompletionSuggestFeature::class,
                        'config' => [
                            'fields' => [
                                'title',
                            ],
                        ],
                    ],
                    'htmlStrip' => [
                        'className' => \PAGEmachine\Searchable\Feature\HtmlStripFeature::class,
                    ],
                ],
                'subCollectors' => [
                    'fal_media' => [
                        'className' => \PAGEmachine\Searchable\DataCollector\TcaDataCollector::class,
                        'config' => [
                            'field' => 'fal_media',
                            'table' => 'sys_file_reference',
                            'fields' => [
                                'identifier',
                            ],
                            'content' => [
                                'config' => [
                                    'sysLanguageOverlay' => 0,
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ],
        'link' => [
            'className' => \PAGEmachine\Searchable\LinkBuilder\NewsLinkBuilder::class,
            'config' => [
                'singlePage' => null,
            ],
        ],
        'preview' => [
            'className' => \PAGEmachine\Searchable\Preview\FluidPreviewRenderer::class,
            'config' => [
                'fields' => [
                    'teaser',
                    'bodytext',
                    'type',
                    'fal_media',
                    'datetime',
                ],
                'templateName' => 'Preview/News',
            ],
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
                'title' => [
                    'type' => 'text',
                ],
                'teaser' => [
                    'type' => 'text',
                ],
                'bodytext' => [
                    'type' => 'text',
                ],
                'internalurl' => [
                    'type' => 'text',
                ],
                'externalurl' => [
                    'type' => 'text',
                ],
            ],
        ],
    ];
}

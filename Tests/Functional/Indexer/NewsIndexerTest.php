<?php

declare(strict_types=1);

namespace PAGEmachine\Searchable\Tests\Functional\Query;

use PAGEmachine\Searchable\Tests\Functional\AbstractElasticsearchTest;

/**
 * Testcase for PAGEmachine\Searchable\Indexer\NewsIndexer
 */
final class NewsIndexerTest extends AbstractElasticsearchTest
{
    protected $indexers = [
        'foo_news' => [
            'className' => \PAGEmachine\Searchable\Indexer\NewsIndexer::class,
            'config' => [
                'collector' => [
                    'config' => [
                        'pid' => 1,
                    ],
                ],
                'link' => [
                    'config' => [
                        'singlePage' => 1,
                    ],
                ],
            ],
        ],
    ];

    /**
     * @test
     */
    public function indexation(): void
    {
        $this->getDatabaseConnection()->insertArray('tx_news_domain_model_news', [
            'uid' => 2,
            'pid' => 1,
            'title' => 'Test news',
            'teaser' => 'Something about Elasticsearch',
            'bodytext' => 'body',
            'path_segment' => 'test-news',
            'type' => 1,
        ]);

        $this->indexingService->indexFull();
        $this->syncIndices();

        $this->assertDocumentInIndex(
            2,
            [
                'title' => 'Test news',
                'teaser' => 'Something about Elasticsearch',
                'bodytext' => 'body',
            ]
        );
    }
}

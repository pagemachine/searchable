<?php

declare(strict_types=1);

namespace PAGEmachine\Searchable\Tests\Functional\Queue;

use PAGEmachine\Searchable\Database\Connection;
use PAGEmachine\Searchable\Indexer\PagesIndexer;
use PAGEmachine\Searchable\Indexer\TcaIndexer;
use PAGEmachine\Searchable\LinkBuilder\TypoLinkBuilder;
use PAGEmachine\Searchable\Preview\NoPreviewRenderer;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class UpdateQueueTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = ['typo3conf/ext/searchable'];

    protected array $configurationToUseInTestInstance = [
        'DB' => [
            'Connections' => [
                'Default' => [
                    'wrapperClass' => Connection::class,
                ],
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();

        ArrayUtility::mergeRecursiveWithOverrule(
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable'],
            [
                'indexers' => [
                    'pages' => [
                        'className' => PagesIndexer::class,
                        'config' => [
                            'collector' => [
                                'config' => ['pid' => 1],
                            ],
                        ],
                    ],
                    'content' => [
                        'className' => TcaIndexer::class,
                        'config' => [
                            'collector' => [
                                'config' => [
                                    'table' => 'tt_content',
                                    'pid' => 1,
                                    'fields' => ['header'],
                                ],
                            ],
                            'preview' => ['className' => NoPreviewRenderer::class],
                            'link' => ['className' => TypoLinkBuilder::class],
                        ],
                    ],
                ],
            ]
        );
    }

    #[Test]
    public function insertDoesNotPolluteLasInsertIdOnDefaultConnection(): void
    {
        $connection = $this->get(ConnectionPool::class)
            ->getConnectionForTable('tt_content');

        $connection->insert('tt_content', [
            'pid' => 1,
            'header' => 'Pollution test',
        ]);

        $reportedId = (int)$connection->lastInsertId();

        $actualUid = (int)$connection->createQueryBuilder()
            ->select('uid')
            ->from('tt_content')
            ->where('header = ' . $connection->quote('Pollution test'))
            ->executeQuery()
            ->fetchOne();

        self::assertSame(
            $actualUid,
            $reportedId,
            'lastInsertId() must return the tt_content uid, not a queue record uid'
        );
    }
}

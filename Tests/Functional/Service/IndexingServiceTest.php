<?php
declare(strict_types = 1);

namespace PAGEmachine\Searchable\Tests\Functional\Service;

use PAGEmachine\Searchable\Service\IndexingService;
use PAGEmachine\Searchable\Tests\Functional\AbstractElasticsearchTest;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\Writer\FileWriter;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Testcase for PAGEmachine\Searchable\Service\IndexingService
 */
final class IndexingServiceTest extends AbstractElasticsearchTest
{
    /**
     * @var array
     */
    protected $configurationToUseInTestInstance = [
        'DB' => [
            'Connections' => [
                'Default' => [
                    'wrapperClass' => \PAGEmachine\Searchable\Database\Connection::class,
                ],
            ],
        ],
        'LOG' => [
            'PAGEmachine' => [
                'Searchable' => [
                    'writerConfiguration' => [
                        LogLevel::DEBUG => [
                            FileWriter::class => [
                                'logFile' => 'typo3temp/var/log/searchable-test.log',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ];

    /**
     * @test
     */
    public function indexesRecordsFully(): void
    {
        $this->getDatabaseConnection()->insertArray('pages', [
            'uid' => 2,
            'pid' => 1,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Test page',
        ]);

        $this->assertIndexEmpty();

        $this->indexingService->indexFull();

        $this->assertDocumentInIndex([
            'uid' => 2,
            'title' => 'Test page',
        ]);
    }

    /**
     * @test
     */
    public function indexesRecordsPartially(): void
    {
        $this->getDatabaseConnection()->insertArray('pages', [
            'uid' => 2,
            'pid' => 1,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Test page',
        ]);
        $this->indexingService->indexFull();

        $this->assertDocumentInIndex([
            'uid' => 2,
            'title' => 'Test page',
        ]);

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('pages');
        $connection->update(
            'pages',
            [
                'title' => 'Updated test page',
            ],
            [
                'uid' => 2,
            ]
        );

        $this->syncIndices();

        $this->indexingService->indexPartial();

        $this->assertDocumentInIndex([
            'uid' => 2,
            'title' => 'Updated test page',
        ]);
    }

    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->indexingService = $objectManager->get(IndexingService::class);
    }
}

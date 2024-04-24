<?php
declare(strict_types = 1);

namespace PAGEmachine\Searchable\Tests\Functional\Service;

use PAGEmachine\Searchable\Database\Connection;
use PAGEmachine\Searchable\Service\IndexingService;
use PAGEmachine\Searchable\Tests\Functional\AbstractElasticsearchTest;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\Writer\FileWriter;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Testcase for PAGEmachine\Searchable\Service\IndexingService
 */
final class IndexingServiceTest extends AbstractElasticsearchTest
{
    /**
     * @var array
     */
    protected array $configurationToUseInTestInstance = [
        'DB' => [
            'Connections' => [
                'Default' => [
                    'wrapperClass' => Connection::class,
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
        $this->insertArray('pages', [
            'uid' => 3,
            'pid' => 1,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Test page',
            'slug' => '/test-page/',
        ]);

        $this->assertIndexEmpty();

        $this->indexingService->indexFull();

        $this->assertDocumentInIndex(
            3,
            [
                'title' => 'Test page',
                'searchable_meta' => [
                    'renderedLink' => '/test-page/',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function indexesRecordTranslations(): void
    {
        $this->insertArray('pages', [
            'uid' => 3,
            'pid' => 1,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Test page',
            'slug' => '/test-page/',
        ]);
        $this->insertArray('pages', [
            'uid' => 4,
            'pid' => 1,
            'sys_language_uid' => 1,
            'l10n_parent' => 3,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Translated test page',
            'slug' => '/translated-test-page/',
        ]);

        $this->assertIndexEmpty(0);
        $this->assertIndexEmpty(1);

        $this->indexingService->indexFull();

        $this->assertDocumentInIndex(
            3,
            [
                'title' => 'Translated test page',
                'searchable_meta' => [
                    'renderedLink' => '/de/translated-test-page/',
                ],
            ],
            1
        );
    }

    /**
     * @test
     */
    public function appliesLanguageForRecordTranslationIndexing(): void
    {
        $this->insertArray('tt_content', [
            'uid' => 1,
            'pid' => 1,
            'header' => 'Test content',
        ]);
        $this->insertArray('tt_content', [
            'uid' => 2,
            'pid' => 1,
            'l18n_parent' => 1, // [sic!]
            'sys_language_uid' => 1,
            'header' => 'Translated test content',
        ]);

        $this->assertIndexEmpty(0);
        $this->assertIndexEmpty(1);

        $this->indexingService->setup();
        $this->indexingService->indexFull('content');

        $this->assertDocumentInIndex(
            1,
            [
                'header' => 'Test content',
                'searchable_meta' => [
                    'preview' => 'Preview: Test content [1]',
                ],
            ],
            0
        );
        $this->assertDocumentInIndex(
            1,
            [
                'header' => 'Translated test content',
                'searchable_meta' => [
                    'preview' => 'Preview: Translated test content [2]',
                ],
            ],
            1
        );
    }

    /**
     * @test
     */
    public function indexesRecordsPartially(): void
    {
        $this->insertArray('pages', [
            'uid' => 3,
            'pid' => 1,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Test page',
            'slug' => '/test-page/',
        ]);

        $this->indexingService->indexFull();

        $this->assertDocumentInIndex(
            3,
            [
                'title' => 'Test page',
            ]
        );

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('pages');
        $connection->update(
            'pages',
            [
                'title' => 'Updated test page',
            ],
            [
                'uid' => 3,
            ]
        );

        $this->syncIndices();

        $this->indexingService->indexPartial();

        $this->assertDocumentInIndex(
            3,
            [
                'title' => 'Updated test page',
            ]
        );
    }

    /**
     * @test
     */
    public function skipsPagesWithNoSearchFromIndexing(): void
    {
        $this->insertArray('pages', [
            'uid' => 3,
            'pid' => 1,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'First page to exclude',
            'no_search' => 1,
        ]);
        $this->insertArray('pages', [
            'uid' => 4,
            'pid' => 3,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'First regular page',
        ]);
        $this->insertArray('pages', [
            'uid' => 5,
            'pid' => 4,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Second page to exclude',
            'no_search' => 1,
        ]);
        $this->insertArray('pages', [
            'uid' => 6,
            'pid' => 5,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Second regular page',
        ]);

        $this->assertIndexEmpty();

        $this->indexingService->indexFull();

        $this->assertDocumentNotInIndex(3);
        $this->assertDocumentInIndex(4);
        $this->assertDocumentNotInIndex(5);
        $this->assertDocumentInIndex(6);
    }

    /**
     * @test
     */
    public function respectsSiteBase(): void
    {
        $siteConfiguration = GeneralUtility::makeInstance(SiteConfiguration::class);
        $configuration = $siteConfiguration->load('100');
        $configuration['base'] = 'https://bar.example.org/';
        $siteConfiguration->write('100', $configuration);

        $configuration = $siteConfiguration->load('200');
        $configuration['base'] = 'https://qux.example.org/';
        $siteConfiguration->write('200', $configuration);

        $this->insertArray('pages', [
            'uid' => 101,
            'pid' => 100,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Bar test page',
            'slug' => '/bar-test-page/',
        ]);
        $this->insertArray('pages', [
            'uid' => 201,
            'pid' => 200,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Qux test page',
            'slug' => '/qux-test-page/',
        ]);

        $this->assertIndexEmpty();

        $this->indexingService->indexFull();

        $this->assertDocumentInIndex(
            101,
            [
                'title' => 'Bar test page',
                'searchable_meta' => [
                    'renderedLink' => 'https://bar.example.org/100/bar-test-page/',
                ],
            ]
        );
        $this->assertDocumentInIndex(
            201,
            [
                'title' => 'Qux test page',
                'searchable_meta' => [
                    'renderedLink' => 'https://qux.example.org/200/qux-test-page/',
                ],
            ]
        );
    }

    /**
     * @test
     */
    public function indexesRecordsOfUnlocalizableTables(): void
    {
        $this->insertArray('tx_unlocalizedtabletest_unlocalizedtable', [
            'uid' => 1,
            'pid' => 1,
            'title' => 'Test',
        ]);

        $this->assertIndexEmpty(0);
        $this->assertIndexEmpty(1);

        $this->indexingService->setup();
        $this->indexingService->indexFull('unlocalized_table');

        $this->assertDocumentInIndex(
            1,
            [
                'title' => 'Test',
            ],
            0,
        );
        $this->assertDocumentNotInIndex(
            1,
            1,
        );
    }

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->indexingService = $this->get(IndexingService::class);
    }
}

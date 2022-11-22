<?php
declare(strict_types = 1);

namespace PAGEmachine\Searchable\Tests\Functional\Service;

use PAGEmachine\Searchable\Service\IndexingService;
use PAGEmachine\Searchable\Tests\Functional\AbstractElasticsearchTest;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Core\Environment;
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
            'slug' => '/test-page/',
        ]);

        $this->assertIndexeEmpty();

        $this->indexingService->indexFull();

        $this->assertDocumentInIndex(
            2,
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
        $this->getDatabaseConnection()->insertArray('pages', [
            'uid' => 2,
            'pid' => 1,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Test page',
            'slug' => '/test-page/',
        ]);
        $this->getDatabaseConnection()->insertArray('pages', [
            'uid' => 3,
            'pid' => 1,
            'sys_language_uid' => 1,
            'l10n_parent' => 2,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Translated test page',
            'slug' => '/translated-test-page/',
        ]);

        $this->assertIndexeEmpty(0);
        $this->assertIndexeEmpty(1);

        $this->indexingService->indexFull();

        $this->assertDocumentInIndex(
            2,
            [
                'title' => 'Translated test page',
                'searchable_meta' => [
                    'renderedLink' => '/da/translated-test-page/',
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
        $this->getDatabaseConnection()->insertArray('tt_content', [
            'uid' => 1,
            'pid' => 1,
            'header' => 'Test content',
        ]);
        $this->getDatabaseConnection()->insertArray('tt_content', [
            'uid' => 2,
            'pid' => 1,
            'l18n_parent' => 1, // [sic!]
            'sys_language_uid' => 1,
            'header' => 'Translated test content',
        ]);

        $this->assertIndexeEmpty(0);
        $this->assertIndexeEmpty(1);

        $this->indexingService->setup();
        $this->indexingService->indexFull('content');

        $client = $this->getElasticsearchClient();
        $client->indices()->refresh();

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
        $this->getDatabaseConnection()->insertArray('pages', [
            'uid' => 2,
            'pid' => 1,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Test page',
            'slug' => '/test-page/',
        ]);

        $this->indexingService->indexFull();

        $this->assertDocumentInIndex(
            2,
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
                'uid' => 2,
            ]
        );

        $this->syncIndices();
        $client = $this->getElasticsearchClient();
        $client->indices()->refresh();
        $this->indexingService->indexPartial();

        $client = $this->getElasticsearchClient();
        $client->indices()->refresh();
        $this->assertDocumentInIndex(
            2,
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
        $this->getDatabaseConnection()->insertArray('pages', [
            'uid' => 2,
            'pid' => 1,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'First page to exclude',
            'no_search' => 1,
        ]);
        $this->getDatabaseConnection()->insertArray('pages', [
            'uid' => 3,
            'pid' => 2,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'First regular page',
        ]);
        $this->getDatabaseConnection()->insertArray('pages', [
            'uid' => 4,
            'pid' => 3,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Second page to exclude',
            'no_search' => 1,
        ]);
        $this->getDatabaseConnection()->insertArray('pages', [
            'uid' => 5,
            'pid' => 4,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Second regular page',
        ]);

        $this->assertIndexeEmpty();

        $this->indexingService->indexFull();

        $this->assertDocumentNotInIndex(2);
        $this->assertDocumentInIndex(3);
        $this->assertDocumentNotInIndex(4);
        $this->assertDocumentInIndex(5);
    }

    /**
     * @test
     */
    public function respectsSiteBase(): void
    {
        $siteConfiguration = GeneralUtility::makeInstance(
            SiteConfiguration::class,
            Environment::getConfigPath() . '/sites'
        );
        $configuration = $siteConfiguration->load('100');
        $configuration['base'] = 'https://bar.example.org/';
        $siteConfiguration->write('100', $configuration);

        $configuration = $siteConfiguration->load('200');
        $configuration['base'] = 'https://qux.example.org/';
        $siteConfiguration->write('200', $configuration);

        $this->getDatabaseConnection()->insertArray('pages', [
            'uid' => 101,
            'pid' => 100,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Bar test page',
            'slug' => '/bar-test-page/',
        ]);
        $this->getDatabaseConnection()->insertArray('pages', [
            'uid' => 201,
            'pid' => 200,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Qux test page',
            'slug' => '/qux-test-page/',
        ]);

        $this->assertIndexeEmpty();

        $this->indexingService->indexFull();

        $client = $this->getElasticsearchClient();
        $client->indices()->refresh();

        $this->assertDocumentInIndex(
            101,
            [
                'title' => 'Bar test page',
                'searchable_meta' => [
                    'renderedLink' => 'https://bar.example.org/bar-test-page/',
                ],
            ]
        );
        $this->assertDocumentInIndex(
            201,
            [
                'title' => 'Qux test page',
                'searchable_meta' => [
                    'renderedLink' => 'https://qux.example.org/qux-test-page/',
                ],
            ]
        );
    }

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->indexingService = $objectManager->get(IndexingService::class);
    }
}

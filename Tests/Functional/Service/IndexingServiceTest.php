<?php
declare(strict_types = 1);

namespace PAGEmachine\Searchable\Tests\Functional\Service;

use Elasticsearch\Client as ElasticsearchClient;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use PAGEmachine\Searchable\Connection;
use PAGEmachine\Searchable\Indexer\PagesIndexer;
use PAGEmachine\Searchable\Service\IndexingService;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\Writer\FileWriter;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Testcase for PAGEmachine\Searchable\Service\IndexingService
 */
final class IndexingServiceTest extends FunctionalTestCase
{
    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/searchable',
    ];

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
     * @var IndexingService
     */
    protected $indexingService;

    /**
     * @var string
     */
    protected $indexName;

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

        $this->indexName = sprintf('index_%s', GeneralUtility::makeInstance(Random::class)->generateRandomHexString(8));

        ArrayUtility::mergeRecursiveWithOverrule(
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable'],
            [
                'extensionManagement' => [
                    'connection' => [
                        'hosts' => sprintf('http://%s', getenv('ELASTICSEARCH_HOST')),
                    ],
                    'indexing' => [
                        'domain' => sprintf('http://%s', getenv('HTTP_HOST')),
                    ],
                ],
                'indices' => [
                    0 => [
                        'name' => $this->indexName,
                    ],
                ],
                'indexers' => [
                    'pages' => [
                        'className' => PagesIndexer::class,
                        'config' => [
                            'type' => 'test_pages',
                            'collector' => [
                                'config' => [
                                    'pid' => 1,
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->indexingService = $objectManager->get(IndexingService::class);
        $this->indexingService->setup();

        $this->getDatabaseConnection()->insertArray('pages', [
            'uid' => 1,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Root',
        ]);
        $this->setUpFrontendRootPage(1, [
            'EXT:searchable/Configuration/Typoscript/setup.txt',
            __DIR__ . '/Fixtures/TypoScript/page.typoscript',
        ]);

        // Necessary for \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseUserPermissionCheck
        $this->setUpBackendUserFromFixture(1);
        // Necessary for \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseSystemLanguageRows
        if (!method_exists(Bootstrap::class, 'getInstance')) { // TYPO3v9+
            Bootstrap::initializeLanguageObject();
        } else {
            Bootstrap::getInstance()->initializeLanguageObject();
        }
    }

    /**
     * @return void
     */
    protected function tearDown()
    {
        $this->getElasticsearchClient()->indices()->delete([
            'index' => $this->indexName,
        ]);
    }

    protected function assertIndexEmpty(): void
    {
        $client = $this->getElasticsearchClient();
        $this->syncIndices();

        $response = $client->search([
            'index' => $this->indexName,
        ]);
        $total = $response['hits']['total'];

        $this->assertEquals(0, $total, 'Documents in index');
    }

    protected function assertDocumentInIndex(array $documentSubset): void
    {
        $client = $this->getElasticsearchClient();
        $this->syncIndices();

        $response = $client->search([
            'index' => $this->indexName,
        ]);
        $hits = $response['hits']['hits'];
        $document = $hits[0]['_source'] ?? [];

        $this->assertGreaterThanOrEqual(1, count($hits), 'No document in index');
        $this->assertNotEmpty($document, 'Document not in index');
        $this->assertArraySubset($documentSubset, $document, false, 'Document source mismatch');
    }

    protected function getElasticsearchClient(): ElasticsearchClient
    {
        $client = Connection::getClient();

        return $client;
    }

    /**
     * Ensure all queued changes are persisted
     */
    protected function syncIndices(): void
    {
        $this->getElasticsearchClient()->indices()->flushSynced([
            'index' => implode(',', [
                $this->indexName,
                'searchable_updates',
            ]),
        ]);
    }
}

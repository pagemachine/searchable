<?php
declare(strict_types = 1);

namespace PAGEmachine\Searchable\Tests\Functional;

use Elasticsearch\Client as ElasticsearchClient;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use PAGEmachine\Searchable\Connection;
use PAGEmachine\Searchable\Indexer\PagesIndexer;
use PAGEmachine\Searchable\Service\IndexingService;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Frontend\Page\PageRepository;

abstract class AbstractElasticsearchTest extends FunctionalTestCase
{
    use WebserverTrait;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/searchable',
    ];

    /**
     * @var string[]
     */
    private $indexNames;

    /**
     * @var IndexingService
     */
    protected $indexingService;

    /**
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();

        $id = GeneralUtility::makeInstance(Random::class)->generateRandomHexString(8);
        $this->indexNames[0] = sprintf('index_%s_en', $id);
        $this->indexNames[1] = sprintf('index_%s_de', $id);

        ArrayUtility::mergeRecursiveWithOverrule(
            $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable'],
            [
                'extensionManagement' => [
                    'connection' => [
                        'hosts' => sprintf('http://%s', getenv('ELASTICSEARCH_HOST')),
                    ],
                    'indexing' => [
                        'domain' => 'http://localhost:8080',
                    ],
                ],
                'indices' => [
                    0 => [
                        'name' => $this->indexNames[0],
                    ],
                    1 => [
                        'name' => $this->indexNames[1],
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

        $this->startWebserver();

        $this->getDatabaseConnection()->insertArray('pages', [
            'uid' => 1,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Root',
        ]);
        $this->setUpFrontendRootPage(1, [
            __DIR__ . '/Fixtures/TypoScript/page.typoscript',
            'EXT:searchable/Configuration/Typoscript/setup.txt',
        ]);
        // Update internally created site to flush all caches
        if (class_exists(SiteConfiguration::class)) {
            $siteConfiguration = GeneralUtility::makeInstance(
                SiteConfiguration::class,
                Environment::getConfigPath() . '/sites'
            );
            $siteConfiguration->write('1', $siteConfiguration->load('1'));
        }

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
            'index' => implode(',', $this->indexNames),
        ]);

        $this->stopWebserver();
    }

    protected function assertIndexEmpty(int $languageId = 0): void
    {
        $client = $this->getElasticsearchClient();
        $this->syncIndices();

        $response = $client->search([
            'index' => $this->indexNames[$languageId],
        ]);
        $total = $response['hits']['total'];

        $this->assertEquals(0, $total, 'Documents in index');
    }

    protected function assertDocumentInIndex(array $documentSubset, int $languageId = 0): void
    {
        $client = $this->getElasticsearchClient();
        $this->syncIndices();

        $response = $client->search([
            'index' => $this->indexNames[$languageId],
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
            'index' => implode(',', array_merge(
                $this->indexNames,
                [ 'searchable_updates' ]
            )),
        ]);
    }
}

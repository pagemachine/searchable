<?php
declare(strict_types = 1);

namespace PAGEmachine\Searchable\Tests\Functional;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Elasticsearch\Client as ElasticsearchClient;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use PAGEmachine\Searchable\Connection;
use PAGEmachine\Searchable\Indexer\PagesIndexer;
use PAGEmachine\Searchable\Indexer\TcaIndexer;
use PAGEmachine\Searchable\Service\IndexingService;
use Pagemachine\SearchableExtbaseL10nTest\Preview\ContentPreviewRenderer;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Frontend\Page\PageRepository;

abstract class AbstractElasticsearchTest extends FunctionalTestCase
{
    use ArraySubsetAsserts;
    use WebserverTrait;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/searchable',
        'typo3conf/ext/searchable/Tests/Functional/Fixtures/Extensions/extbase_l10n_test',
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
    protected function setUp(): void
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
                    'foo_pages' => [
                        'className' => PagesIndexer::class,
                        'config' => [
                            'type' => 'foo_pages',
                            'collector' => [
                                'config' => [
                                    'pid' => 1,
                                ],
                            ],
                        ],
                    ],
                    'bar_pages' => [
                        'className' => PagesIndexer::class,
                        'config' => [
                            'type' => 'bar_pages',
                            'collector' => [
                                'config' => [
                                    'pid' => 100,
                                ],
                            ],
                        ],
                    ],
                    'qux_pages' => [
                        'className' => PagesIndexer::class,
                        'config' => [
                            'type' => 'qux_pages',
                            'collector' => [
                                'config' => [
                                    'pid' => 200,
                                ],
                            ],
                        ],
                    ],
                    'content' => [
                        'className' => TcaIndexer::class,
                        'config' => [
                            'type' => 'content',
                            'collector' => [
                                'config' => [
                                    'table' => 'tt_content',
                                    'pid' => 1,
                                    'fields' => [
                                        'header',
                                    ],
                                ],
                            ],
                            'preview' => [
                                'className' => ContentPreviewRenderer::class,
                            ],
                        ],
                    ],
                ],
            ]
        );

        $this->getDatabaseConnection()->insertArray('pages', [
            'uid' => 1,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Foo Root',
        ]);
        $this->getDatabaseConnection()->insertArray('pages', [
            'uid' => 100,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Bar Root',
        ]);
        $this->getDatabaseConnection()->insertArray('pages', [
            'uid' => 200,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Qux Root',
        ]);

        $typoScriptConstantsFile = 'EXT:searchable/Configuration/TypoScript/constants.typoscript';
        $typoScriptSetupFile = 'EXT:searchable/Configuration/TypoScript/setup.typoscript';
        if (version_compare(VersionNumberUtility::getCurrentTypo3Version(), '9', '<')) {
            $typoScriptConstantsFile = 'EXT:searchable/Configuration/TypoScript/constants.txt';
            $typoScriptSetupFile = 'EXT:searchable/Configuration/TypoScript/setup.txt';
        }
        $this->setUpFrontendRootPage(1, [
            __DIR__ . '/Fixtures/TypoScript/page.typoscript',
            $typoScriptSetupFile,
        ]);
        $this->setUpFrontendRootPage(100);
        $this->setUpFrontendRootPage(200);
        $this->getDatabaseConnection()->updateArray(
            'sys_template',
            [
                'pid' => 1,
            ],
            [
                'constants' => '<INCLUDE_TYPOSCRIPT: source="FILE:' . $typoScriptConstantsFile . '">',
            ]
        );

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->indexingService = $objectManager->get(IndexingService::class);
        $this->indexingService->setup();

        $this->startWebserver();

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
    protected function tearDown(): void
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

    protected function assertDocumentInIndex(int $uid, array $documentSubset = [], int $languageId = 0): void
    {
        $document = $this->searchDocumentByUid($uid, $languageId);

        $this->assertNotEmpty($document, 'Document not in index');
        $this->assertArraySubset($documentSubset, $document, false, 'Document source mismatch');
    }

    protected function assertDocumentNotInIndex(int $uid, int $languageId = 0): void
    {
        $document = $this->searchDocumentByUid($uid, $languageId);

        $this->assertEmpty($document, 'Document in index');
    }

    protected function getElasticsearchClient(): ElasticsearchClient
    {
        $client = Connection::getClient();

        return $client;
    }

    protected function searchDocumentByUid(int $uid, int $languageId): array
    {
        $client = $this->getElasticsearchClient();
        $this->syncIndices();

        $response = $client->search([
            'index' => $this->indexNames[$languageId],
            'body' => [
                'query' => [
                    'term' => [
                        'uid' => [
                            'value' => $uid,
                        ],
                    ],
                ],
            ],
        ]);
        $hits = $response['hits']['hits'];
        $document = $hits[0]['_source'] ?? [];

        return $document;
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

<?php
declare(strict_types = 1);

namespace PAGEmachine\Searchable\Tests\Functional;

use DMS\PHPUnitExtensions\ArraySubset\ArraySubsetAsserts;
use Elasticsearch\Client as ElasticsearchClient;
use PAGEmachine\Searchable\Connection;
use PAGEmachine\Searchable\Indexer\PagesIndexer;
use PAGEmachine\Searchable\Indexer\TcaIndexer;
use PAGEmachine\Searchable\LinkBuilder\TypoLinkBuilder;
use PAGEmachine\Searchable\Preview\NoPreviewRenderer;
use PAGEmachine\Searchable\Service\IndexingService;
use PAGEmachine\Searchable\Tests\Functional\SiteBasedTestTrait;
use Pagemachine\SearchableExtbaseL10nTest\Preview\ContentPreviewRenderer;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

abstract class AbstractElasticsearchTest extends FunctionalTestCase
{
    use ArraySubsetAsserts;
    use WebserverTrait;
    use SiteBasedTestTrait;

    /**
     * @var array
     */
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/searchable',
        'typo3conf/ext/searchable/Tests/Functional/Fixtures/Extensions/extbase_l10n_test',
        'typo3conf/ext/searchable/Tests/Functional/Fixtures/Extensions/unlocalized_table_test',
        'typo3conf/ext/searchable/Tests/Functional/Fixtures/Extensions/test_webserver',
    ];

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8'],
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
                            'link' => [
                                'className' => TypoLinkBuilder::class,
                            ],
                        ],
                    ],
                    'unlocalized_table' => [
                        'className' => TcaIndexer::class,
                        'config' => [
                            'type' => 'unlocalized_table',
                            'collector' => [
                                'config' => [
                                    'table' => 'tx_unlocalizedtabletest_unlocalizedtable',
                                    'pid' => 1,
                                    'fields' => [
                                        'title',
                                    ],
                                ],
                            ],
                            'preview' => [
                                'className' => NoPreviewRenderer::class,
                            ],
                            'link' => [
                                'className' => TypoLinkBuilder::class,
                            ],
                        ],
                    ],
                ],
            ]
        );

        $this->insertArray('pages', [
            'uid' => 1,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Foo Root',
        ]);
        $this->insertArray('pages', [
            'uid' => 2,
            'pid' => 1,
            'sys_language_uid' => 1,
            'l10n_parent' => 1,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Dansk Foo Root',
        ]);
        $this->insertArray('pages', [
            'uid' => 100,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Bar Root',
        ]);
        $this->insertArray('pages', [
            'uid' => 200,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Qux Root',
        ]);

        $rootPageConfig = [
            'setup' => [
                __DIR__ . '/Fixtures/TypoScript/page.typoscript',
                'EXT:searchable/Configuration/TypoScript/setup.typoscript',
            ],
            'constants' => [
                'EXT:searchable/Configuration/TypoScript/constants.typoscript',
            ],
        ];
        $this->setUpFrontendRootPage(1, $rootPageConfig);
        $this->setUpFrontendRootPage(100, $rootPageConfig);
        $this->setUpFrontendRootPage(200, $rootPageConfig);

        $this->indexingService = $this->get(IndexingService::class);
        $this->indexingService->setup();

        $this->startWebserver();

        $this->writeSiteConfiguration(
            '1',
            $this->buildSiteConfiguration(1, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildDefaultLanguageConfiguration('DE', '/de/'),
            ]
        );
        $this->writeSiteConfiguration(
            '100',
            $this->buildSiteConfiguration(100, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '100/'),
            ]
        );
        $this->writeSiteConfiguration(
            '200',
            $this->buildSiteConfiguration(200, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '200/'),
            ]
        );


        $request = (new ServerRequest('https://foo.de', 'GET'));
        $normalizedParams = NormalizedParams::createFromRequest($request);
        $request = $request
            ->withAttribute('normalizedParams', $normalizedParams)
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('typo3.testing.context', new InternalRequestContext());
        $GLOBALS['TYPO3_REQUEST'] = $request;

        // Necessary for \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseUserPermissionCheck
        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_users.csv');
        $this->setUpBackendUser(1);

        // Necessary for \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseSystemLanguageRows
        Bootstrap::initializeLanguageObject();
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

    protected function insertArray($tableName, $record)
    {
        return $this->getConnectionPool()->getConnectionForTable($tableName)->insert($tableName, $record);
    }

    protected function updateArray($tableName, $record, $identifier)
    {
        return $this->getConnectionPool()->getConnectionForTable($tableName)->update($tableName, $record, $identifier);
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

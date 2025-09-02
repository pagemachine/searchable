<?php

declare(strict_types=1);

namespace PAGEmachine\Searchable\Tests\Functional;

use Elasticsearch\Client as ElasticsearchClient;
use PAGEmachine\Searchable\Connection;
use PAGEmachine\Searchable\Indexer\PagesIndexer;
use PAGEmachine\Searchable\Indexer\TcaIndexer;
use PAGEmachine\Searchable\LinkBuilder\TypoLinkBuilder;
use PAGEmachine\Searchable\Preview\NoPreviewRenderer;
use PAGEmachine\Searchable\Service\ExtconfService;
use PAGEmachine\Searchable\Service\IndexingService;
use Pagemachine\SearchableExtbaseL10nTest\Preview\ContentPreviewRenderer;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\VisibilityAspect;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

abstract class AbstractElasticsearchTest extends FunctionalTestCase
{
    use WebserverTrait;
    use SiteBasedTestTrait;

    /**
     * @var array
     */
    protected array $testExtensionsToLoad = [
        'typo3conf/ext/searchable',
        'typo3conf/ext/searchable/Tests/Functional/Fixtures/Extensions/extbase_l10n_test',
        'typo3conf/ext/searchable/Tests/Functional/Fixtures/Extensions/unlocalized_table_test',
    ];

    protected $frameworkExtensionsToLoad = [
        'Resources/Core/Functional/Extensions/private_container',
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
     * @var string[]
     */
    protected $configIndexNames;

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
        $this->configIndexNames = [sprintf('index_%s_en', $id), sprintf('index_%s_de', $id)];

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
                    $this->configIndexNames[0] => [
                        'typo3_language' => 0,
                    ],
                    $this->configIndexNames[1] => [
                        'typo3_language' => 1,
                    ],
                ],
                'indexers' => [
                    'foo_pages' => [
                        'className' => PagesIndexer::class,
                        'config' => [
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

        $this->indexNames = ExtconfService::getIndices();

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
                'EXT:searchable/Tests/Functional/Fixtures/TypoScript/page.typoscript',
                'EXT:searchable/Configuration/TypoScript/setup.typoscript',
            ],
            'constants' => [
                'EXT:searchable/Configuration/TypoScript/constants.typoscript',
            ],
        ];
        $this->setUpFrontendRootPage(1, $rootPageConfig);
        $this->setUpFrontendRootPage(100, $rootPageConfig);
        $this->setUpFrontendRootPage(200, $rootPageConfig);

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

        // Necessary for \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseUserPermissionCheck
        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);

        if (version_compare(GeneralUtility::makeInstance(Typo3Version::class)->getVersion(), '13.0', '>=')) {
            $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        } else {
            // Necessary for \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseSystemLanguageRows
            Bootstrap::initializeLanguageObject();
        }

        $context = GeneralUtility::makeInstance(Context::class);
        $currentVisibilityAspect = $context->getAspect('visibility');
        $context->setAspect('visibility', new VisibilityAspect(
            includeHiddenPages: true,
            includeHiddenContent: false,
            includeDeletedRecords: $currentVisibilityAspect->get('includeDeletedRecords'),
            includeScheduledRecords: $currentVisibilityAspect->get('includeScheduledRecords'),
        ));

        $this->indexingService = $this->get(IndexingService::class);
        $this->indexingService->setup();

        $this->startWebserver();
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

        unset($this->indexingService);

        parent::tearDown();
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
            'index' => implode(',', ExtconfService::getIndicesByLanguage($languageId)),
        ]);
        $total = $response['hits']['total']['value'];

        $this->assertEquals(0, $total, 'Documents in index');
    }

    protected function assertDocumentInIndex(int $uid, array $documentSubset = [], int $languageId = 0): void
    {
        $document = $this->searchDocumentByUid($uid, $languageId);

        $this->assertNotEmpty($document, sprintf('Document %d not in index', $uid));
        $this->assertArraySubset($documentSubset, $document, sprintf('Document %d source mismatch', $uid));
    }

    private function assertArraySubset(array $subset, array $array, string $message = ''): void
    {
        foreach ($subset as $key => $expectedValue) {
            $this->assertArrayHasKey($key, $array, $message ?: sprintf('Key "%s" not found in array', $key));

            if (is_array($expectedValue)) {
                $this->assertIsArray($array[$key], $message ?: sprintf('Key "%s" is not an array', $key));
                $this->assertArraySubset($expectedValue, $array[$key], $message);
            } else {
                $this->assertEquals($expectedValue, $array[$key], $message ?: sprintf('Key "%s" value mismatch', $key));
            }
        }
    }

    protected function assertDocumentNotInIndex(int $uid, int $languageId = 0): void
    {
        $document = $this->searchDocumentByUid($uid, $languageId);

        $this->assertEmpty($document, sprintf('Document %d in index', $uid));
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
            'index' => implode(',', ExtconfService::getIndicesByLanguage($languageId)),
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
        $this->getElasticsearchClient()->indices()->refresh();
    }
}

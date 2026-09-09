<?php

declare(strict_types=1);

namespace PAGEmachine\Searchable\Tests\Functional\Service;

use PAGEmachine\Searchable\Database\Connection;
use PAGEmachine\Searchable\Service\IndexingService;
use PAGEmachine\Searchable\Tests\Functional\AbstractElasticsearchTestCase;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\Writer\FileWriter;
use TYPO3\CMS\Core\Resource\FileType;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Testcase for PAGEmachine\Searchable\Service\IndexingService
 */
final class IndexingServiceTest extends AbstractElasticsearchTestCase
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

    #[Test]
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

    #[Test]
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

    #[Test]
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

    #[Test]
    public function indexesTablesWithFluidPreviewAndHtml(): void
    {
        $this->insertArray('pages', [
            'uid' => 3,
            'pid' => 1,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Test page',
            'slug' => '/test-page/',
        ]);
        $this->insertArray('tt_content', [
            'uid' => 1,
            'pid' => 1,
            'CType' => 'textmedia',
            'header' => 'Test content',
            'header_layout' => 1,
            'bodytext' => '<p>Some <strong>important</strong> content with <a href="t3://page?uid=3">link</a>.</p>',
        ]);

        $this->assertIndexEmpty();

        $this->indexingService->indexFull('content_fluid_preview');

        $this->assertDocumentInIndex(
            1,
            [
                'header' => 'Test content',
                'bodytext' => '<p>Some <strong>important</strong> content with <a href="t3://page?uid=3">link</a>.</p>',
            ]
        );

        $expectedPreview = <<<HTML
        <div id="c1" class="frame frame-default frame-type-textmedia frame-layout-0"><header><h1 class="">
                        Test content
                    </h1></header><div class="ce-textpic ce-center ce-above"><div class="ce-bodytext"><p>Some <strong>important</strong> content with <a href="/test-page/">link</a>.</p></div></div></div>
        HTML;
        $document = $this->searchDocumentByUid(1, 0);
        $normalizedPreview = trim(preg_replace('/>\s+</', '><', $document['searchable_meta']['preview'] ?? ''));

        self::assertSame($expectedPreview, $normalizedPreview);
    }

    #[Test]
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

    #[Test]
    public function indexesHiddenRecordsPartially(): void
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
                'hidden' => 1,
            ],
            [
                'uid' => 3,
            ]
        );

        $this->syncIndices();

        $this->indexingService->indexPartial();

        $this->assertDocumentNotInIndex(3);
    }

    #[Test]
    public function removesFileDocumentFromIndexWhenMetadataIsDeletedByFileIdentifier(): void
    {
        $this->createLocalFileStorage();
        $this->createIndexableFile(5, 42, 'Test file');

        $this->assertIndexEmpty();

        $this->indexingService->setup();
        $this->indexingService->indexFull('files');

        $this->assertDocumentInIndex(
            42,
            [
                'title' => 'Test file',
            ]
        );

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_file_metadata');

        self::assertInstanceOf(Connection::class, $connection);

        $affectedRows = $connection->delete('sys_file_metadata', ['file' => 5]);

        self::assertSame(1, $affectedRows);

        $this->syncIndices();

        $this->indexingService->indexPartial();

        $this->assertDocumentNotInIndex(42);
    }

    #[Test]
    public function leavesOtherFileDocumentsInIndexWhenUnrelatedMetadataIsDeleted(): void
    {
        $this->createLocalFileStorage();
        $this->createIndexableFile(5, 42, 'Test file');
        $this->createIndexableFile(6, 43, 'Other test file');

        $this->indexingService->setup();
        $this->indexingService->indexFull('files');

        $this->assertDocumentInIndex(42, ['title' => 'Test file']);
        $this->assertDocumentInIndex(43, ['title' => 'Other test file']);

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('sys_file_metadata');

        $affectedRows = $connection->delete('sys_file_metadata', ['file' => 5]);

        self::assertSame(1, $affectedRows);

        $this->syncIndices();

        $this->indexingService->indexPartial();

        $this->assertDocumentNotInIndex(42);
        $this->assertDocumentInIndex(43, ['title' => 'Other test file']);
    }

    #[Test]
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

    #[Test]
    public function respectsSiteBase(): void
    {
        $siteConfiguration = GeneralUtility::makeInstance(SiteConfiguration::class);
        $siteWriter = GeneralUtility::makeInstance(SiteWriter::class);

        $configuration = $siteConfiguration->load('100');
        $configuration['base'] = 'https://bar.example.org/';
        $siteWriter->write('100', $configuration);

        $configuration = $siteConfiguration->load('200');
        $configuration['base'] = 'https://qux.example.org/';
        $siteWriter->write('200', $configuration);

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

    #[Test]
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

    #[Test]
    public function indexesPagesWithinTransientPages(): void
    {
        $this->insertArray('pages', [
            'uid' => 3,
            'pid' => 1,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Regular page',
        ]);
        $this->insertArray('pages', [
            'uid' => 4,
            'pid' => 1,
            'doktype' => PageRepository::DOKTYPE_LINK,
            'title' => 'Link page',
            ...((new Typo3Version())->getMajorVersion() < 14 ? [] : ['link' => 3]),
        ]);
        $this->insertArray('pages', [
            'uid' => 5,
            'pid' => 4,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Link nested page',
        ]);
        $this->insertArray('pages', [
            'uid' => 6,
            'pid' => 1,
            'doktype' => PageRepository::DOKTYPE_SHORTCUT,
            'shortcut_mode' => PageRepository::SHORTCUT_MODE_FIRST_SUBPAGE,
            'title' => 'Shortcut page',
        ]);
        $this->insertArray('pages', [
            'uid' => 7,
            'pid' => 6,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Shortcut nested page',
        ]);
        $this->insertArray('pages', [
            'uid' => 8,
            'pid' => 1,
            'doktype' => PageRepository::DOKTYPE_SPACER,
            'shortcut_mode' => PageRepository::SHORTCUT_MODE_FIRST_SUBPAGE,
            'title' => 'Spacer page',
        ]);
        $this->insertArray('pages', [
            'uid' => 9,
            'pid' => 8,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Spacer nested page',
        ]);

        $this->indexingService->resetIndex();
        $this->indexingService->indexFull('foo_pages');

        $this->assertDocumentInIndex(3);
        $this->assertDocumentNotInIndex(4);
        $this->assertDocumentInIndex(5);
        $this->assertDocumentNotInIndex(6);
        $this->assertDocumentInIndex(7);
        $this->assertDocumentNotInIndex(8);
        $this->assertDocumentInIndex(9);
    }

    #[Test]
    public function indexesPagesWithinHiddenPages(): void
    {
        $this->insertArray('pages', [
            'uid' => 3,
            'pid' => 1,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'First visible page',
        ]);
        $this->insertArray('pages', [
            'uid' => 4,
            'pid' => 3,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Second visible page',
        ]);
        $this->insertArray('pages', [
            'uid' => 5,
            'pid' => 3,
            'hidden' => 1,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'First hidden page',
        ]);
        $this->insertArray('pages', [
            'uid' => 6,
            'pid' => 5,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Third visible page',
        ]);
        $this->insertArray('pages', [
            'uid' => 7,
            'pid' => 3,
            'hidden' => 1,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Second hidden page',
        ]);
        $this->insertArray('pages', [
            'uid' => 8,
            'pid' => 7,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Fourth visible page',
        ]);

        $this->indexingService->resetIndex();
        $this->indexingService->indexFull('foo_pages');

        $this->assertDocumentInIndex(3);
        $this->assertDocumentInIndex(4);
        $this->assertDocumentNotInIndex(5);
        $this->assertDocumentInIndex(6);
        $this->assertDocumentNotInIndex(7);
        $this->assertDocumentInIndex(8);
    }

    #[Test]
    public function indexesPageOverlayWithFallbackTypeStrict(): void
    {
        $this->writeSiteConfiguration(
            '1',
            $this->buildSiteConfiguration(1, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de/', [], 'strict'),
            ]
        );

        // EN-only page
        $this->insertArray('pages', [
            'uid' => 300,
            'pid' => 1,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'EN only page',
            'slug' => '/en-only/',
        ]);

        $this->assertIndexEmpty(0);
        $this->assertIndexEmpty(1);

        $this->indexingService->resetIndex();
        $this->indexingService->indexFull('foo_pages');

        // Present in EN
        $this->assertDocumentInIndex(
            300,
            [
                'title' => 'EN only page',
                'searchable_meta' => [
                    'renderedLink' => '/en-only/',
                ],
            ],
            0
        );
        // Not present in DE due to strict (no fallbacks)
        $this->assertDocumentNotInIndex(300, 1);
    }

    #[Test]
    public function indexesPageOverlayWithFallbackTypeFallback(): void
    {
        $this->writeSiteConfiguration(
            '1',
            $this->buildSiteConfiguration(1, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de/', ['EN'], 'fallback'),
            ]
        );

        // EN-only page
        $this->insertArray('pages', [
            'uid' => 301,
            'pid' => 1,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'EN fallback page',
            'slug' => '/en-fallback/',
        ]);

        $this->assertIndexEmpty(0);
        $this->assertIndexEmpty(1);

        $this->indexingService->resetIndex();
        $this->indexingService->indexFull('foo_pages');

        // Present in EN
        $this->assertDocumentInIndex(
            301,
            [
                'title' => 'EN fallback page',
                'searchable_meta' => [
                    'renderedLink' => '/en-fallback/',
                ],
            ],
            0
        );

        // Also present in DE due to fallback
        $this->assertDocumentInIndex(
            301,
            [
                'title' => 'EN fallback page',
                'searchable_meta' => [
                    // expect DE base prefixed link
                    'renderedLink' => '/de/en-fallback/',
                ],
            ],
            1
        );
    }

    /**
     * Creates a "1:/" local file storage backed by a real, writable directory
     * inside the functional test instance, so files placed there are actually
     * readable by \TYPO3\CMS\Core\Resource\ResourceFactory, as required by
     * FileIndexer::sendBatch().
     */
    private function createLocalFileStorage(): void
    {
        $basePath = Environment::getPublicPath() . '/fileadmin/';
        GeneralUtility::mkdir_deep($basePath);

        $this->insertArray('sys_file_storage', [
            'uid' => 1,
            'pid' => 0,
            'name' => 'fileadmin/',
            'driver' => 'Local',
            'is_online' => 1,
            'is_browsable' => 1,
            'is_public' => 1,
            'is_writable' => 1,
            'is_default' => 1,
            'configuration' => '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>'
                . '<T3FlexForms><data><sheet index="sDEF"><language index="lDEF">'
                . '<field index="basePath"><value index="vDEF">fileadmin/</value></field>'
                . '<field index="pathType"><value index="vDEF">relative</value></field>'
                . '<field index="caseSensitive"><value index="vDEF">1</value></field>'
                . '</language></sheet></data></T3FlexForms>',
        ]);
    }

    /**
     * Creates a real sys_file + sys_file_metadata pair backed by an actual
     * file on disk in the "1:/" storage created by createLocalFileStorage().
     * A real file is required because FileIndexer::sendBatch() reads its
     * content via ResourceFactory::getFileObject()->getContents() and drops
     * the record from the batch entirely if that fails.
     */
    private function createIndexableFile(int $fileUid, int $metadataUid, string $title): void
    {
        $basePath = Environment::getPublicPath() . '/fileadmin/';
        $fileName = sprintf('test-%d.txt', $fileUid);
        $fileContents = sprintf('Contents of %s', $title);

        file_put_contents($basePath . $fileName, $fileContents);

        $this->insertArray('sys_file', [
            'uid' => $fileUid,
            'pid' => 0,
            'storage' => 1,
            'identifier' => '/' . $fileName,
            'identifier_hash' => sha1('/' . $fileName),
            'folder_hash' => sha1('/'),
            'extension' => 'txt',
            'mime_type' => 'text/plain',
            'name' => $fileName,
            'sha1' => sha1($fileContents),
            'size' => strlen($fileContents),
            'type' => FileType::TEXT->value,
        ]);

        $this->insertArray('sys_file_metadata', [
            'uid' => $metadataUid,
            'pid' => 0,
            'file' => $fileUid,
            'title' => $title,
            'description' => 'Created for functional testing',
        ]);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->indexingService = $this->get(IndexingService::class);
    }
}

<?php
declare(strict_types = 1);

namespace PAGEmachine\Searchable\Tests\Functional\Query;

use PAGEmachine\Searchable\Query\SearchQuery;
use PAGEmachine\Searchable\Tests\Functional\AbstractElasticsearchTest;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Testcase for PAGEmachine\Searchable\Query\SearchQuery
 */
final class SearchQueryTest extends AbstractElasticsearchTest
{
    /**
     * @test
     */
    public function searchesByTerm(): void
    {
        $this->insertArray('pages', [
            'uid' => 3,
            'pid' => 1,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Test page',
        ]);
        $this->insertArray('pages', [
            'uid' => 4,
            'pid' => 1,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Another test page',
        ]);
        $this->insertArray('pages', [
            'uid' => 5,
            'pid' => 1,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Unrelated page',
        ]);

        $this->indexingService->indexFull();
        $this->syncIndices();

        $query = GeneralUtility::makeInstance(SearchQuery::class);
        $query->setRespectLanguage(false);
        $query->setTerm('test');

        $result = $query->execute();

        $this->assertEquals(2, $result['hits']['total']['value']);
        $this->assertEquals('Test page', $result['hits']['hits'][0]['_source']['title']);
        $this->assertEquals('Another test page', $result['hits']['hits'][1]['_source']['title']);
    }

    /**
     * @test
     */
    public function searchesByTermWithHighlighting(): void
    {
        $this->insertArray('pages', [
            'uid' => 12,
            'pid' => 1,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Test page Highlighting and more',
        ]);

        $this->indexingService->indexFull();
        $this->syncIndices();

        $query = GeneralUtility::makeInstance(SearchQuery::class);
        $query->setRespectLanguage(false);
        $query->setTerm('highlighting');
        $result = $query->execute();

        $this->assertEquals(1, $result['hits']['total']['value']);
        $this->assertStringContainsString(
            "<span class='searchable-highlight'>Highlighting</span>",
            $result['hits']['hits'][0]['highlight']['searchable_highlight'][0]
        );
    }

    /**
     * @test
     */
    public function searchesByLanguages(): void
    {
        $this->insertArray('pages', [
            'uid' => 3,
            'pid' => 1,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Test page',
        ]);
        $this->insertArray('pages', [
            'uid' => 10,
            'pid' => 1,
            'sys_language_uid' => 1,
            'l10n_parent' => 3,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Dansk test page',
        ]);
        $this->insertArray('pages', [
            'uid' => 4,
            'pid' => 1,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Another test page',
        ]);


        $this->indexingService->indexFull();
        $this->syncIndices();

        $query = GeneralUtility::makeInstance(SearchQuery::class);
        $query->setLanguage(0);
        $query->setTerm('test');

        $result = $query->execute();

        $this->assertEquals(2, $result['hits']['total']['value']);
        $this->assertEquals('Test page', $result['hits']['hits'][0]['_source']['title']);
        $this->assertEquals('Another test page', $result['hits']['hits'][1]['_source']['title']);

        $query = GeneralUtility::makeInstance(SearchQuery::class);
        $query->setLanguage(1);
        $query->setTerm('test');

        $result = $query->execute();

        $this->assertEquals(1, $result['hits']['total']['value']);
        $this->assertEquals('Dansk test page', $result['hits']['hits'][0]['_source']['title']);
    }

    /**
     * @test
     */
    public function normalizeIndicesWithIndex(): void
    {
        $query = GeneralUtility::makeInstance(SearchQuery::class);
        $query->setLanguage(0);
        $indices = [$this->configIndexNames[1] . '_foo_pages'];

        $query->setIndices($indices);
        $this->assertEquals([], $query->getElasticsearchIndices());
        $query->setRespectLanguage(false);
        $this->assertEquals($indices, $query->getElasticsearchIndices());
    }

    /**
     * @test
     */
    public function normalizeIndicesWithIndexer(): void
    {
        $query = GeneralUtility::makeInstance(SearchQuery::class);
        $query->setLanguage(0);

        $query->setIndices(['foo_pages']);
        $this->assertEquals([$this->configIndexNames[0] . '_foo_pages'], $query->getElasticsearchIndices());
        $query->setRespectLanguage(false);
        $this->assertEquals([$this->configIndexNames[0] . '_foo_pages', $this->configIndexNames[1] . '_foo_pages'], $query->getElasticsearchIndices());
    }
}

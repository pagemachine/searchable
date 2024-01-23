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
        $this->getDatabaseConnection()->insertArray('pages', [
            'uid' => 2,
            'pid' => 1,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Test page',
        ]);
        $this->getDatabaseConnection()->insertArray('pages', [
            'uid' => 3,
            'pid' => 1,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Another test page',
        ]);
        $this->getDatabaseConnection()->insertArray('pages', [
            'uid' => 4,
            'pid' => 1,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Unrelated page',
        ]);

        $this->indexingService->indexFull();
        $this->syncIndices();
        $query = GeneralUtility::makeInstance(SearchQuery::class);
        $query->setTerm('test');
        $client = $this->getElasticsearchClient();
        $client->indices()->refresh();

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
        $this->getDatabaseConnection()->insertArray('pages', [
            'uid' => 2,
            'pid' => 1,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'Test page',
        ]);
        $this->getDatabaseConnection()->insertArray('tt_content', [
            'pid' => 2,
            'header' => 'Test header',
            'bodytext' => 'Something about Highlighting and Elasticsearch',
        ]);
        $this->indexingService->indexFull();
        $this->syncIndices();

        $query = GeneralUtility::makeInstance(SearchQuery::class);
        $query->setTerm('highlighting');
        $client = $this->getElasticsearchClient();
        $client->indices()->refresh();
        $result = $query->execute();

        $this->assertEquals(1, $result['hits']['total']['value']);
        $this->assertStringContainsString(
            "<span class='searchable-highlight'>Highlighting</span>",
            $result['hits']['hits'][0]['highlight']['searchable_highlight'][0]
        );
    }
}

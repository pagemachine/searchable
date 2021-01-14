<?php
declare(strict_types = 1);

namespace PAGEmachine\Searchable\Tests\Functional\Query;

use PAGEmachine\Searchable\Database\Connection;
use PAGEmachine\Searchable\Query\UpdateQuery;
use PAGEmachine\Searchable\Tests\Functional\AbstractElasticsearchTest;

/**
 * Testcase for PAGEmachine\Searchable\Query\UpdateQuery
 */
final class UpdateQueryTest extends AbstractElasticsearchTest
{
    /**
     * This configuration array is merged with TYPO3_CONF_VARS
     * that are set in default configuration and factory configuration
     *
     * @var array
     */
    protected $configurationToUseInTestInstance = [
        'DB' => [
            'Connections' => [
                'Default' => [
                    'wrapperClass' => Connection::class,
                ],
            ],
        ],
    ];

    /**
     * @test
     */
    public function handlesLargeAmountOfSublevelUpdates(): void
    {
        $database = $this->getDatabaseConnection();

        // Default for "indices.query.bool.max_clause_count" is 1024
        foreach (range(1, 1025) as $uid) {
            $database->insertArray(
                'tt_content',
                [
                    'uid' => $uid,
                    'pid' => 1,
                    'header' => sprintf('Content %d', $uid),
                ]
            );
        }

        $this->indexingService->indexFull();

        foreach (range(1, 1025) as $uid) {
            $database->updateArray(
                'tt_content',
                [
                    'uid' => $uid,
                ],
                [
                    'header' => sprintf('Updated content %d', $uid),
                ]
            );
        }

        $this->syncIndices();

        $updateQuery = new UpdateQuery();
        $updates = $updateQuery->getUpdates(
            $this->getIndexName(),
            'test_pages'
        );

        $this->assertCount(0, $updates);
    }
}

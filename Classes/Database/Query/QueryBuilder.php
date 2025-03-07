<?php
namespace PAGEmachine\Searchable\Database\Query;

/*
 * This file is part of the Pagemachine Searchable project.
 */

use Doctrine\DBAL\Query\QueryBuilder as DoctrineQueryBuilder;
use PAGEmachine\Searchable\Query\DatabaseRecordUpdateQuery;
use TYPO3\CMS\Core\Database\Query\QueryBuilder as BaseQueryBuilder;

/**
 * Query builder which tracks inserts/updates for partial index updates
 */
class QueryBuilder extends BaseQueryBuilder
{
    /**
     * @var \PAGEmachine\Searchable\Database\Connection
     */
    protected $connection;

    /**
     * @var DatabaseRecordUpdateQuery
     */
    protected $updateQuery;

    /**
     * Executes this query using the bound parameters and their types.
     *
     * @return \Doctrine\DBAL\Driver\Statement|int
     */
    public function execute()
    {
        $result = parent::execute();

        if ($this->getType() === DoctrineQueryBuilder::INSERT) {
            $tableName = $this->connection->unquoteIdentifier($this->getQueryPart('from')['table']);
            $this->getQuery()->updateToplevel($tableName, (int)$this->connection->lastInsertId($tableName));
        } elseif (in_array($this->getType(), [DoctrineQueryBuilder::UPDATE, DoctrineQueryBuilder::DELETE], true)) {
            $tableName = $this->connection->unquoteIdentifier($this->getQueryPart('from')['table']);
            $where = (string)$this->getQueryPart('where');
            $matches = [];
            $count = preg_match('/[^\w]*uid[^\w]\s*=\s*(?:(?<uid>[0-9]+)|:(?<placeholder>[\w]+))/', $where, $matches);

            if ($count === 1) {
                $uid = 0;

                if (!empty($matches['uid'])) {
                    $uid = $matches['uid'];
                } elseif (!empty($matches['placeholder'])) {
                    $uid = $this->getParameter($matches['placeholder']);
                }

                $this->getQuery()->updateToplevel($tableName, (int)$uid);
                $this->getQuery()->updateSublevel($tableName, (int)$uid);
            }
        }

        return $result;
    }

    /**
     * @return DatabaseRecordUpdateQuery
     */
    protected function getQuery(): DatabaseRecordUpdateQuery
    {
        if ($this->updateQuery == null) {
            $this->updateQuery = new DatabaseRecordUpdateQuery();
        }

        return $this->updateQuery;
    }
}

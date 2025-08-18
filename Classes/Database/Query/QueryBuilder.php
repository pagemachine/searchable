<?php
namespace PAGEmachine\Searchable\Database\Query;

/*
 * This file is part of the Pagemachine Searchable project.
 */

use Doctrine\DBAL\Exception\DriverException;
use Doctrine\DBAL\Query\QueryBuilder as DoctrineQueryBuilder;
use Doctrine\DBAL\Query\QueryType;
use PAGEmachine\Searchable\Query\DatabaseRecordUpdateQuery;
use TYPO3\CMS\Core\Database\Query\QueryBuilder as BaseQueryBuilder;

/**
 * Query builder which tracks inserts/updates for partial index updates
 */
class QueryBuilder extends BaseQueryBuilder
{
    /**
     * @var DatabaseRecordUpdateQuery
     */
    protected $updateQuery;

    /**
     * Tracks changes for partial index updates.
     */
    public function executeStatement(): int
    {
        $affected = parent::executeStatement();

        $fromParts = $this->getFromHelper();
        if (!empty($fromParts)) {
            $fromTable = $fromParts['table'] ?: $fromParts[0]->table;
            $tableName = $this->unquoteSingleIdentifier($fromTable);

            if ($this->getTypeHelper() === $this->queryType()::INSERT) {
                try {
                    $this->getQuery()->updateToplevel($tableName, (int)$this->connection->lastInsertId($tableName));
                } catch (DriverException) {
                    // Could not retrieve lastInsertedId
                }
            } elseif ($this->getTypeHelper() === $this->queryType()::UPDATE || $this->getTypeHelper() === $this->queryType()::DELETE) {
                $whereExpr = $this->getWhereHelper();
                $where = is_string($whereExpr) ? $whereExpr : (string)($whereExpr ?? '');
                $matches = [];
                $count = preg_match('/[^\w]*uid[^\w]\s*=\s*(?:(?<uid>[0-9]+)|:(?<placeholder>[\w]+))/', $where, $matches);
                if ($count === 1) {
                    $uid = 0;
                    if (!empty($matches['uid'])) {
                        $uid = (int)$matches['uid'];
                    } elseif (!empty($matches['placeholder'])) {
                        $uid = (int)$this->getParameter($matches['placeholder']);
                    }
                    if ($uid > 0) {
                        $this->getQuery()->updateToplevel($tableName, $uid);
                        $this->getQuery()->updateSublevel($tableName, $uid);
                    }
                }
            }
        }

        return $affected;
    }

    protected function getFromHelper(): array
    {
        if (method_exists($this, 'getFrom')) {
            return $this->getFrom();
        }

        if (method_exists($this, 'getQueryPart')) {
            return $this->getQueryPart('from') ?: [];
        }

        throw new \RuntimeException('Could not retrieve FROM');
    }

    protected function getWhereHelper()
    {
        if (method_exists($this, 'getWhere')) {
            return $this->getWhere();
        }

        if (method_exists($this, 'getQueryPart')) {
            return $this->getQueryPart('where');
        }

        throw new \RuntimeException('Could not retrieve WHERE');
    }

    protected function getTypeHelper()
    {
        if (method_exists($this, 'getType')) {
            return $this->getType();
        }

        if (property_exists($this, 'type')) {
            return $this->type;
        }

        throw new \RuntimeException('Could not retrieve TYPE');
    }

    protected function queryType()
    {
        if (class_exists(QueryType::class)) {
            return QueryType::class;
        }

        return DoctrineQueryBuilder::class;
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

<?php
namespace PAGEmachine\Searchable\Database;

/*
 * This file is part of the Pagemachine Searchable project.
 */

use PAGEmachine\Searchable\Database\Query\QueryBuilder;
use PAGEmachine\Searchable\Query\DatabaseRecordUpdateQuery;
use TYPO3\CMS\Core\Database\Connection as BaseConnection;
use TYPO3\CMS\Core\Database\Query\QueryBuilder as BaseQueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Connection which tracks inserts/updates for partial index updates
 */
class Connection extends BaseConnection
{
    /**
     * @var DatabaseRecordUpdateQuery
     */
    protected $updateQuery;

    /**
     * Creates a new instance of a SQL query builder.
     *
     * @return \TYPO3\CMS\Core\Database\Query\QueryBuilder
     */
    public function createQueryBuilder(): BaseQueryBuilder
    {
        return GeneralUtility::makeInstance(QueryBuilder::class, $this);
    }

    /**
     * Inserts a table row with specified data.
     *
     * All SQL identifiers are expected to be unquoted and will be quoted when building the query.
     * Table expression and columns are not escaped and are not safe for user-input.
     *
     * @param string $tableName The name of the table to insert data into.
     * @param array $data An associative array containing column-value pairs.
     * @param array $types Types of the inserted data.
     *
     * @return int The number of affected rows.
     */
    public function insert($tableName, array $data, array $types = []): int
    {
        $result = parent::insert(...func_get_args());

        $this->getQuery()->updateToplevel($tableName, (int)$this->lastInsertId($tableName));

        // Special treatment for tt_content (since no connection to the pages record is triggered by the insert)
        if ($tableName == 'tt_content' && !empty($data['pid'])) {
            $this->getQuery()->updateToplevel('pages', (int)$data['pid']);
        }

        return $result;
    }

    /**
     * Executes an SQL UPDATE statement on a table.
     *
     * All SQL identifiers are expected to be unquoted and will be quoted when building the query.
     * Table expression and columns are not escaped and are not safe for user-input.
     *
     * @param string $tableName The name of the table to update.
     * @param array $data An associative array containing column-value pairs.
     * @param array $identifier The update criteria. An associative array containing column-value pairs.
     * @param array $types Types of the merged $data and $identifier arrays in that order.
     *
     * @return int The number of affected rows.
     */
    public function update($tableName, array $data, array $identifier, array $types = []): int
    {
        $result = parent::update(...func_get_args());

        if (!empty($identifier['uid'])) {
            $this->getQuery()->updateToplevel($tableName, (int)$identifier['uid']);
            $this->getQuery()->updateSublevel($tableName, (int)$identifier['uid']);
        }

        return $result;
    }

    /**
     * Executes an SQL DELETE statement on a table.
     *
     * All SQL identifiers are expected to be unquoted and will be quoted when building the query.
     * Table expression and columns are not escaped and are not safe for user-input.
     *
     * @param string $tableName The name of the table on which to delete.
     * @param array $identifier The deletion criteria. An associative array containing column-value pairs.
     * @param array $types The types of identifiers.
     *
     * @return int The number of affected rows.
     */
    public function delete($tableName, array $identifier, array $types = []): int
    {
        $result = parent::delete(...func_get_args());

        if (!empty($identifier['uid'])) {
            $this->getQuery()->updateToplevel($tableName, (int)$identifier['uid']);
            $this->getQuery()->updateSublevel($tableName, (int)$identifier['uid']);
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

    /**
     * Unquotes an identifier
     *
     * @param string $identifier The identifier name to be unquoted
     * @return string The unquoted identifier string
     * @see \Doctrine\DBAL\Platforms\AbstractPlatform::quoteIdentifier()
     */
    public function unquoteIdentifier(string $identifier): string
    {
        $c = $this->getDatabasePlatform()->getIdentifierQuoteCharacter();

        return trim(str_replace($c . $c, $c, $identifier), $c);
    }
}

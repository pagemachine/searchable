<?php
namespace PAGEmachine\Searchable\Database;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

use PAGEmachine\Searchable\Configuration\ConfigurationManager;
use PAGEmachine\Searchable\Query\UpdateQuery;
use TYPO3\CMS\Core\Database\Connection as BaseConnection;

/**
 * Connection which tracks inserts/updates for partial index updates
 */
class Connection extends BaseConnection
{
    /**
     * @var UpdateQuery
     */
    protected $updateQuery;

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

        $this->registerToplevelUpdate($tableName, (int)$this->lastInsertId($tableName));

        // Special treatment for tt_content (since no connection to the pages record is triggered by the insert)
        if ($tableName == 'tt_content' && !empty($data['pid'])) {
            $this->registerToplevelUpdate('pages', (int)$data['pid']);
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
            $this->registerToplevelUpdate($tableName, (int)$identifier['uid']);
            $this->registerSublevelUpdates($tableName, (int)$identifier['uid']);
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
            $this->registerToplevelUpdate($tableName, (int)$identifier['uid']);
            $this->registerSublevelUpdates($tableName, (int)$identifier['uid']);
        }

        return $result;
    }

    /**
     * Register a toplevel update
     *
     * @param string $table
     * @param int $uid
     * @return void
     */
    protected function registerToplevelUpdate(string $table, int $uid)
    {
        $updateConfiguration = ConfigurationManager::getInstance()->getUpdateConfiguration();

        if (!empty($updateConfiguration['database']['toplevel'][$table])) {
            foreach ($updateConfiguration['database']['toplevel'][$table] as $type) {
                $this->getQuery()->addUpdate($type, 'uid', $uid);
            }
        }
    }

    /**
     * Register sublevel updates
     *
     * @param string $table
     * @param int $uid
     * @return void
     */
    protected function registerSublevelUpdates(string $table, int $uid)
    {
        $updateConfiguration = ConfigurationManager::getInstance()->getUpdateConfiguration();

        if (!empty($updateConfiguration['database']['sublevel'][$table])) {
            foreach ($updateConfiguration['database']['sublevel'][$table] as $type => $path) {
                $this->getQuery()->addUpdate($type, $path . '.uid', $uid);
            }
        }
    }

    /**
     * @return UpdateQuery
     */
    protected function getQuery(): UpdateQuery
    {
        if ($this->updateQuery == null) {
            $this->updateQuery = new UpdateQuery();
        }

        return $this->updateQuery;
    }
}

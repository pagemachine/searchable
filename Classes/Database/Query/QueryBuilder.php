<?php
namespace PAGEmachine\Searchable\Database\Query;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

use Doctrine\DBAL\Query\QueryBuilder as DoctrineQueryBuilder;
use PAGEmachine\Searchable\Configuration\ConfigurationManager;
use PAGEmachine\Searchable\Query\UpdateQuery;
use TYPO3\CMS\Core\Database\Query\QueryBuilder as BaseQueryBuilder;

/**
 * Query builder which tracks inserts/updates for partial index updates
 */
class QueryBuilder extends BaseQueryBuilder
{
    /**
     * Executes this query using the bound parameters and their types.
     *
     * @return \Doctrine\DBAL\Driver\Statement|int
     */
    public function execute()
    {
        $result = parent::execute();

        if ($this->getType() === DoctrineQueryBuilder::INSERT) {
            $tableName = $this->getConnection()->unquoteIdentifier($this->getQueryPart('from')['table']);
            $this->registerToplevelUpdate($tableName, (int)$this->getConnection()->lastInsertId($tableName));
        } elseif (in_array($this->getType(), [DoctrineQueryBuilder::UPDATE, DoctrineQueryBuilder::DELETE], true)) {
            $tableName = $this->getConnection()->unquoteIdentifier($this->getQueryPart('from')['table']);
            $where = (string)$this->getQueryPart('where');
            $matches = [];
            $count = preg_match('/[^\w]*uid[^\w]\s*=\s*(?:(?<uid>[0-9]+)|:(?<placeholder>[\w]+))/', $where, $matches);

            if ($count === 1) {
                if (!empty($matches['uid'])) {
                    $uid = $matches['uid'];
                } elseif (!empty($matches['placeholder'])) {
                    $uid = $this->getParameter($matches['placeholder']);
                }

                $this->registerToplevelUpdate($tableName, (int)$uid);
                $this->registerSublevelUpdates($tableName, (int)$uid);
            }
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

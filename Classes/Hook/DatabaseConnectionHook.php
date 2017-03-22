<?php
namespace PAGEmachine\Searchable\Hook;

use PAGEmachine\Searchable\Configuration\ConfigurationManager;
use PAGEmachine\Searchable\Query\UpdateQuery;
use TYPO3\CMS\Core\Database\PostProcessQueryHookInterface;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

class DatabaseConnectionHook implements PostProcessQueryHookInterface
{

    /**
     * @var UpdateQuery
     */
    protected $updateQuery;

    public function __construct() {

        $this->updateQuery = new UpdateQuery();
    }


    /**
    * Post-processor for the SELECTquery method.
    *
    * @param string $select_fields Fields to be selected
    * @param string $from_table Table to select data from
    * @param string $where_clause Where clause
    * @param string $groupBy Group by statement
    * @param string $orderBy Order by statement
    * @param int $limit Database return limit
    * @param \TYPO3\CMS\Core\Database\DatabaseConnection $parentObject
    * @return void
    */
    public function exec_SELECTquery_postProcessAction(&$select_fields, &$from_table, &$where_clause, &$groupBy, &$orderBy, &$limit, \TYPO3\CMS\Core\Database\DatabaseConnection $parentObject)
    {
        //Nothing to do here
    }

    /**
    * Post-processor for the exec_INSERTquery method.
    *
    * @param string $table Database table name
    * @param array $fieldsValues Field values as key => value pairs
    * @param string|array $noQuoteFields List/array of keys NOT to quote
    * @param \TYPO3\CMS\Core\Database\DatabaseConnection $parentObject
    * @return void
    */
    public function exec_INSERTquery_postProcessAction(&$table, array &$fieldsValues, &$noQuoteFields, \TYPO3\CMS\Core\Database\DatabaseConnection $parentObject)
    {
        $this->registerToplevelUpdate($table, 'uid=' . $parentObject->sql_insert_id());

        //Special treatment for tt_content (since no connection to the pages record is triggered by the insert)
        if ($table == 'tt_content') {

            $this->registerToplevelUpdate("pages", "uid=" . $fieldsValues['pid']);
        }
    }

    /**
    * Post-processor for the exec_INSERTmultipleRows method.
    *
    * @param string $table Database table name
    * @param array $fields Field names
    * @param array $rows Table rows
    * @param string|array $noQuoteFields List/array of keys NOT to quote
    * @param \TYPO3\CMS\Core\Database\DatabaseConnection $parentObject
    * @return void
    */
    public function exec_INSERTmultipleRows_postProcessAction(&$table, array &$fields, array &$rows, &$noQuoteFields, \TYPO3\CMS\Core\Database\DatabaseConnection $parentObject)
    {
    }

    /**
    * Post-processor for the exec_UPDATEquery method.
    *
    * @param string $table Database table name
    * @param string $where WHERE clause
    * @param array $fieldsValues Field values as key => value pairs
    * @param string|array $noQuoteFields List/array of keys NOT to quote
    * @param \TYPO3\CMS\Core\Database\DatabaseConnection $parentObject
    * @return void
    */
    public function exec_UPDATEquery_postProcessAction(&$table, &$where, array &$fieldsValues, &$noQuoteFields, \TYPO3\CMS\Core\Database\DatabaseConnection $parentObject)
    {
        $this->registerToplevelUpdate($table, $where);
        $this->registerSublevelUpdates($table, $where);
    }

    /**
    * Post-processor for the exec_DELETEquery method.
    *
    * @param string $table Database table name
    * @param string $where WHERE clause
    * @param \TYPO3\CMS\Core\Database\DatabaseConnection $parentObject
    * @return void
    */
    public function exec_DELETEquery_postProcessAction(&$table, &$where, \TYPO3\CMS\Core\Database\DatabaseConnection $parentObject)
    {
        $this->registerToplevelUpdate($table, $where);
        $this->registerSublevelUpdates($table, $where);        
    }

    /**
    * Post-processor for the exec_TRUNCATEquery method.
    *
    * @param string $table Database table name
    * @param \TYPO3\CMS\Core\Database\DatabaseConnection $parentObject
    * @return void
    */
    public function exec_TRUNCATEquery_postProcessAction(&$table, \TYPO3\CMS\Core\Database\DatabaseConnection $parentObject)
    {
    }


    /**
     * Adds updates to ES
     *
     * @param string $table
     * @param string $where
     */
    protected function registerToplevelUpdate($table, $where) {

        $updateConfiguration = ConfigurationManager::getInstance()->getUpdateConfiguration();

        //If table is toplevel, mark the corresponding document as updated
        if (is_string($updateConfiguration['database']['toplevel'][$table])) {

            $uidMatch = [];
            if (preg_match("/^uid=([0-9]*)$/", $where, $uidMatch)) {
                
                $this->updateQuery->addUpdate($updateConfiguration['database']['toplevel'][$table], "uid", $uidMatch[1]);
            }
        }

    }

    /**
     * Adds updates to ES
     *
     * @param string $table
     * @param string $where
     */
    protected function registerSublevelUpdates($table, $where) {

        $updateConfiguration = ConfigurationManager::getInstance()->getUpdateConfiguration();

        if (array_key_exists($table, $updateConfiguration['database']['sublevel']) && !empty($updateConfiguration['database']['sublevel'][$table])) {

            foreach ($updateConfiguration['database']['sublevel'][$table] as $typeName => $path) {

                $uidMatch = [];
                if (preg_match("/^uid=([0-9]*)$/", $where, $uidMatch)) {

                    $this->updateQuery->addUpdate($typeName, $path . ".uid", $uidMatch[1]);
                }
            }
        }
    }
}

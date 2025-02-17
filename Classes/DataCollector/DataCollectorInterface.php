<?php
namespace PAGEmachine\Searchable\DataCollector;

/*
 * This file is part of the Pagemachine Searchable project.
 */

/**
 * DataCollectorInterface
 */
interface DataCollectorInterface
{
    /**
     * Returns all subCollectors
     *
     * @return array
     */
    public function getSubCollectors();

    /**
     * Adds a new SubCollector for subtypes
     *
     * @param string                 $field        Fieldname to apply this collector to
     */
    public function addSubCollector($field, DataCollectorInterface $subCollector);

    /**
     * Returns a subcollector by given field
     *
     * @param  string $field
     * @return DataCollectorInterface $subCollector
     */
    public function getSubCollectorForField($field);


    /**
     * Returns the merged configuration (default + custom)
     *
     * @return array
     */
    public function getConfig();

    /**
     * Fetches a list of records
     *
     * @return \Generator
     */
    public function getRecords();

    /**
     * Fetches the list of updated records
     *
     * @param  array $updateUidList The list of updated uids coming from the Elasticsearch update index
     * @return \Generator
     */
    public function getUpdatedRecords($updateUidList);

    /**
     * Fetches a single record
     *
     * @param  int $identifier
     * @return array
     */
    public function getRecord($identifier);
}

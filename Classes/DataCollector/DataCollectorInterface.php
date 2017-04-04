<?php
namespace PAGEmachine\Searchable\DataCollector;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * DataCollectorInterface
 */
interface DataCollectorInterface {

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
	 * @param DataCollectorInterface $subCollector
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
     * @param int|null $pid
     * @return array
     */
    public function getRecords($pid = null);

    /**
     * Fetches a single record
     * 
     * @param  int $identifier
     * @return array
     */
    public function getRecord($identifier);

    /**
     * Checks if a record still exists. This is needed for the update scripts
     *
     * @param  int $identifier
     * @return bool
     */
    public function exists($identifier);

}

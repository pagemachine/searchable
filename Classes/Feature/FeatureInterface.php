<?php
namespace PAGEmachine\Searchable\Feature;

use PAGEmachine\Searchable\Query\QueryInterface;


/*
 * This file is part of the PAGEmachine Searchable project.
 */

interface FeatureInterface {

    /**
     * Entry point to modify mapping.
     * Static to improve performance
     *
     * @param  array  $mapping
     * @param  array  $configuration
     * @return array  $mapping
     */
    public static function modifyMapping($mapping, $configuration);

    /**
     * Entry point to modify records before insert/update
     *
     * @param  array  $record
     * @return array  $record
     */
    public function modifyRecord($record);

    /**
     * Modifies a query before it is executed
     *
     * @param QueryInterface $query
     * @return array
     */
    public function modifyQuery(QueryInterface $query);
}

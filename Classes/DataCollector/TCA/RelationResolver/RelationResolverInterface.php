<?php
namespace PAGEmachine\Searchable\DataCollector\TCA\RelationResolver;

use PAGEmachine\Searchable\DataCollector\DataCollectorInterface;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * 
 */
interface RelationResolverInterface {

    /**
     * Resolves relation
     *
     * @param  mixed $rawField
     * @param  array $fieldTca The TCA config of this relation
     * @param  DataCollectorInterface $collector
     * @return array $processedField
     */
    public function resolveRelation($rawField, $fieldTca, DataCollectorInterface $collector);

}

<?php
namespace PAGEmachine\Searchable\DataCollector\RelationResolver;

use PAGEmachine\Searchable\DataCollector\DataCollectorInterface;

/*
 * This file is part of the Pagemachine Searchable project.
 */

/**
 *
 */
interface RelationResolverInterface
{
    /**
     * Resolves relation
     *
     * @param  string $fieldname The name of the field. Either represents the database/TCA fieldname or - in other cases - just the array key
     * @param  array $record The record containing the field to resolve
     * @return array $processedField
     */
    public function resolveRelation($fieldname, $record, DataCollectorInterface $childCollector, DataCollectorInterface $parentCollector);
}

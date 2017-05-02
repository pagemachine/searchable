<?php
namespace PAGEmachine\Searchable\DataCollector\RelationResolver\FormEngine;

use PAGEmachine\Searchable\DataCollector\DataCollectorInterface;
use PAGEmachine\Searchable\DataCollector\RelationResolver\RelationResolverInterface;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * 
 */
class InlineRelationResolver implements SingletonInterface, RelationResolverInterface {

    /**
     *
     * @return SelectRelationResolver
     */
    public static function getInstance() {

        return GeneralUtility::makeInstance(self::class);
    }

    /**
     * Resolves a select relation. Placeholder relation resolver until RelationResolvers are integrated into FormEngine DataProviders
     *
     * @param  string $fieldname
     * @param  array $record The record containing the field to resolve
     * @param  DataCollectorInterface $childCollector
     * @param  DataCollectorInterface $parentCollector
     * @return array $processedField
     */
    public function resolveRelation($fieldname, $record, DataCollectorInterface $childCollector, DataCollectorInterface $parentCollector) {

        return $record[$fieldname];

    }



}

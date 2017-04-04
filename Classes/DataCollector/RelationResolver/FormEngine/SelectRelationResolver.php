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
class SelectRelationResolver implements SingletonInterface, RelationResolverInterface {

    /**
     *
     * @return SelectRelationResolver
     */
    public static function getInstance() {

        return GeneralUtility::makeInstance(self::class);
    }

    /**
     * Resolves a select relation. Separates actual records from static fields and calls the specified collector for them
     *
     * @param  string $fieldname
     * @param  array $record The record containing the field to resolve
     * @param  DataCollectorInterface $childCollector
     * @param  DataCollectorInterface $parentCollector
     * @return array $processedField
     */
    public function resolveRelation($fieldname, $record, DataCollectorInterface $childCollector, DataCollectorInterface $parentCollector) {

        $parentConfiguration = $parentCollector->getConfig();
        $fieldTca = $GLOBALS['TCA'][$parentConfiguration['table']]['columns'][$fieldname];

        $rawField = $record[$fieldname];

    	$records = [];

        if ($fieldTca['config']['foreign_table']) {

            $records = [];
            if (!empty($rawField) && is_array($rawField)) {

                foreach ($rawField as $key => $value) {

                    if (!is_numeric($value) || (int)$value < 0) {

                        $records[$key] = $value;
                    }

                    $childRecord = $childCollector->getRecord($value);
                    if (!empty($childRecord)) {

                        $records[$key] = $childRecord;
                    }
                }
            }
        }

        return $records;

    }



}

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
     * Resolves a select relation. Separates actual records from static fields and calls the specified collector for them
     *
     * @param  string $fieldname
     * @param  array $record The record containing the field to resolve
     * @param  DataCollectorInterface $childCollector
     * @param  DataCollectorInterface $parentCollector
     * @return array $processedField
     */
    public function resolveRelation($fieldname, $record, DataCollectorInterface $childCollector, DataCollectorInterface $parentCollector) {

        if (!empty($record[$fieldname]) && is_array($record[$fieldname])) {

            $uidList = explode(",", $record[$fieldname]);
            $records = [];

            foreach($uidList as $uid) {

                if ($childCollector->exists($uid)) {

                    $records[] = $childCollector->getRecord($uid);
                }
            }

            return $records;

        }

        return [];

    }



}

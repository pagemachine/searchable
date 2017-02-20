<?php
namespace PAGEmachine\Searchable\DataCollector\TCA\RelationResolver;

use PAGEmachine\Searchable\DataCollector\DataCollectorInterface;
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
     * @return FormDataRecord
     */
    public static function getInstance() {

        return GeneralUtility::makeInstance(self::class);
    }

    /**
     * Resolves a select relation. Separates actual records from static fields and calls the specified collector for them
     *
     * @param  mixed $rawField
     * @param  array $fieldTca The TCA config of this relation
     * @param  DataCollectorInterface $collector
     * @return array $processedField
     */
    public function resolveRelation($rawField, $fieldTca, DataCollectorInterface $collector) {

    	$records = [];

        if ($fieldTca['config']['foreign_table']) {

            $records = [];
            if (!empty($rawField) && is_array($rawField)) {

                foreach ($rawField as $key => $value) {

                    if (!is_numeric($value) || (int)$value < 0) {

                        $records[$key] = $value;
                    }

                    $records[$key] = $collector->getRecord($value);
                }
            }
        }

        return $records;

    }



}

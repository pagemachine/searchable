<?php
namespace PAGEmachine\Searchable\DataCollector\Relation;

use PAGEmachine\Searchable\DataCollector\TcaRecord;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * 
 */
class Select implements SingletonInterface {

    /**
     *
     * @return FormDataRecord
     */
    public static function getInstance() {

        return GeneralUtility::makeInstance(self::class);
    }

    /**
     * Resolves a relation and marks fields which add a new record level
     *
     * @param  mixed $rawField
     * @param  array $fieldTca The TCA config of this relation
     * @param  array $configuration The configuration of this indexing
     * @param  TcaRecord $collector
     * @return array $processedField array in the format ['records' => [...], 'static' => [...]]
     */
    public function resolveRelation($rawField, $fieldTca, $configuration, TcaRecord $collector) {

    	$records = [];

        if ($fieldTca['config']['foreign_table']) {

            $records = [];
            if (!empty($rawField) && is_array($rawField)) {

                foreach ($rawField as $key => $value) {

                    if (!is_numeric($value) || (int)$value < 0) {

                        $records[$key] = $value;
                    }

                    $records[$key] = $collector->getRecord($value, $fieldTca['config']['foreign_table'], $configuration);
                }
            }
        }

        return $records;

    }



}
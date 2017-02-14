<?php
namespace PAGEmachine\Searchable\DataCollector;

use PAGEmachine\Searchable\DataCollector\Relation\Select;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Class for fetching TCA-based data according to the given config
 */
class TcaRecord {

    /**
     *
     * @var array
     */
    protected $toplevelConfiguration;

    /**
     *
     * @param array $config
     */
    public function __construct($config = []) {

        $this->toplevelConfiguration = $config;
    }



    /**
     * Fetches a single record
     * 
     * @param integer $uid
     * @param array $configuration The configuration for this record type
     * @return array
     */
    public function getRecord($uid, $table, $configuration) {

        $record = FormDataRecord::getInstance()->getRecord($uid, $table);

        //Cleanup
        $record = $this->removeExcludedFields($record, $configuration);

        if (!empty($configuration['subtypes'])) {

            foreach ($configuration['subtypes'] as $fieldname => $subconfig) {

                $record[$fieldname] = $this->fetchSubtypeField(
                    $record[$fieldname], 
                    $GLOBALS['TCA'][$this->toplevelConfiguration['table']]['columns'][$fieldname], 
                    $subconfig, 
                    $record['uid']
                );

            }            
        }



        //@todo: Add field cleanup and subtype handling here


        return $record;


    }

    /**
     * Removes excluded fields from record
     *
     * @param  array $record
     * @param  array $configuration
     * @return array $record
     */
    protected function removeExcludedFields($record, $configuration) {

        $excludeFields = $configuration['excludeFields'] ? array_merge($this->toplevelConfiguration['systemExcludeFields'], $configuration['excludeFields']) : $this->toplevelConfiguration['systemExcludeFields'];

        if (!empty($excludeFields)) {

            foreach ($excludeFields as $excludeField) {

                if (array_key_exists($excludeField, $record)) {

                    unset($record[$excludeField]);
                }
            }

        }

        return $record;

    }

    /**
     * Fetches a subtype relation field (select, group etc.)
     *
     * @param  mixed $rawField The field as it is returned from the Formengine. Contains the necessary uids
     * @param  array $fieldTca
     * @param  array $config parent configuration
     * @param  integer $parentUid The current parent record
     * @return array
     */
    protected function fetchSubtypeField($rawField, $fieldTca, $configuration, $parentUid) {

        $resolvedField = Select::getInstance()->resolveRelation($rawField, $fieldTca, $configuration['config'], $this);

        return $resolvedField;


    }








}

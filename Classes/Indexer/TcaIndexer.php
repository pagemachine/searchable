<?php
namespace PAGEmachine\Searchable\Indexer;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Simple TCA based indexer reading fields and processing them
 */
class TcaIndexer extends Indexer implements IndexerInterface {

    /**
     * @var array
     */
    protected static $defaultConfiguration = [
        'collector' => [
            'className' => \PAGEmachine\Searchable\DataCollector\TcaDataCollector::class
        ]
    ];

    /**
     * Main function for indexing
     * 
     * @return array
     */
    public function run() {

        $recordUidList = $this->dataCollector->getRecordList();

        foreach ($recordUidList as $item) {

            $fullRecord = $this->dataCollector->getRecord($item['uid']);
            $fullRecord = $this->addSystemFields($fullRecord);

            $this->query->addRow($item['uid'], $fullRecord);
        }

        $response = $this->query->execute();

        return $response;

    }








}

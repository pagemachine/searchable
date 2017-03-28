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
     * @return \Generator
     */
    public function run() {

        $counter = 0;

        foreach ($this->dataCollector->getRecords() as $fullRecord) {

            $fullRecord = $this->addSystemFields($fullRecord);

            $this->query->addRow($item['uid'], $fullRecord);

            $counter++;

            if ($counter >= 1) {

                $this->query->execute();
                $this->query->resetBody();
                $counter = 0;
                yield "20 records processed.";
            }
        }

    }

}

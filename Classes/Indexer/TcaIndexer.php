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
        $overallCounter = 0;

        foreach ($this->dataCollector->getRecords() as $fullRecord) {

            $fullRecord = $this->addSystemFields($fullRecord);

            $this->query->addRow($fullRecord['uid'], $fullRecord);

            $counter++;
            $overallCounter++;

            if ($counter >= 20) {

                $this->query->execute();
                $this->query->resetBody();

                $counter = 0;
                yield $overallCounter;
            }
        }

        if ($counter != 0) {

            $this->query->execute();
            $this->query->resetBody();
            yield $overallCounter;
        }

    }

}

<?php
namespace PAGEmachine\Searchable\Indexer;

use PAGEmachine\Searchable\DataCollector\TcaBasedDataCollector;
use PAGEmachine\Searchable\Query\BulkQuery;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Simple TCA based indexer reading fields and processing them
 */
class TcaBasedIndexer extends Indexer {


    /**
     * The array wrapper class holding all parameters
     * @var BulkQuery
     */
    protected $query;

    /**
     * Configuration array holding all options needed for this indexer
     *
     * @var array
     */
    protected $config  = [
        'type' => '',
        'fields' => [],
        'subtypes' => []
    ];


    /**
     * Main function for indexing
     * 
     * @return array
     */
    public function run() {

        $this->query = new BulkQuery($this->index, $this->type);

        $dataCollector = new TcaBasedDataCollector($this->config);

        $records = $dataCollector->getRecords();

        foreach ($records as $record) {

            $this->query->addRow($record['uid'], $record);
        }

        $response = $this->query->execute();

        return $response;

    }








}

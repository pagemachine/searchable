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

}

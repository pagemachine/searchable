<?php
namespace PAGEmachine\Searchable\Indexer;

use PAGEmachine\Searchable\DataCollector\TcaDataCollector;
use PAGEmachine\Searchable\Mapper\DefaultMapper;

/*
 * This file is part of the PAGEmachine Searchable project.
 */
/**
 * Simple TCA based indexer reading fields and processing them
 */
class TcaIndexer extends Indexer
{
    /**
     * @var array
     */
    protected static $defaultConfiguration = [
        'collector' => [
            'className' => TcaDataCollector::class,
        ],
        'mapper' => [
            'className' => DefaultMapper::class,
        ],
    ];
}

<?php
namespace PAGEmachine\Searchable\Tests\Unit\Configuration\Fixtures;

use PAGEmachine\Searchable\DataCollector\AbstractDataCollector;
use PAGEmachine\Searchable\Indexer\Indexer;


/*
 * This file is part of the PAGEmachine Searchable project.
 */


class TestDataCollectorFixture extends AbstractDataCollector
{
    protected static $defaultConfiguration = [
        'option1' => 1,
        'option2' => 2
    ];

    /**
     * Fetches a record
     * 
     * @param  int $identifier
     * @return array
     */
    public function getRecord($identifier)
    {

        return [];
    }

    /**
     * Checks if a record still exists. This is needed for the update scripts
     *
     * @param  int $identifier
     * @return bool
     */
    public function exists($identifier)
    {
        return true;
    }

}

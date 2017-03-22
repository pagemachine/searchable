<?php
namespace PAGEmachine\Searchable\Tests\Unit\Configuration\Fixtures;

use PAGEmachine\Searchable\DataCollector\TcaDataCollector;


/*
 * This file is part of the PAGEmachine Searchable project.
 */


class TcaDataCollectorFixture extends TcaDataCollector
{
    protected static $defaultConfiguration = [
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

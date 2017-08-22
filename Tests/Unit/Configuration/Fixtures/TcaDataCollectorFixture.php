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

    public function getRecords()
    {
        return [];
    }
}

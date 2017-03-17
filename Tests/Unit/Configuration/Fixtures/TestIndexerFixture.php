<?php
namespace PAGEmachine\Searchable\Tests\Unit\Configuration\Fixtures;

use PAGEmachine\Searchable\Indexer\Indexer;


/*
 * This file is part of the PAGEmachine Searchable project.
 */


class TestIndexerFixture extends Indexer
{
    protected static $defaultConfiguration = [
        'customOption' => 1
    ];
}

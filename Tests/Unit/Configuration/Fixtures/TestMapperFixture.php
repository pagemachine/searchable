<?php
namespace PAGEmachine\Searchable\Tests\Unit\Configuration\Fixtures;

use PAGEmachine\Searchable\Mapper\DefaultMapper;

/*
 * This file is part of the Pagemachine Searchable project.
 */


class TestMapperFixture extends DefaultMapper
{
    /**
     * Creates the mapping
     *
     * @param  array $indexerConfiguration The toplevel configuration for one indexer
     * @return array
     */
    public static function getMapping($indexerConfiguration)
    {
        $mapping = [];

        $mapping['properties']['overrideKey'] = 'mapperValue';
        $mapping['properties']['newKey'] = 'newMapperValue';

        return $mapping;
    }
}

<?php
namespace PAGEmachine\Searchable\Mapper;


/*
 * This file is part of the PAGEmachine Searchable project.
 */

interface MapperInterface {

    /**
     * Creates a mapping array for the given index
     *
     * @param  array $indexerConfiguration
     * @return array $mapping
     */
    public static function getMapping($indexerConfiguration);

}

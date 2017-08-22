<?php
namespace PAGEmachine\Searchable\Mapper;

use PAGEmachine\Searchable\Service\ExtconfService;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * DefaultMapper
 * Creates necessary meta fields and appends them to the mapping array
 */
class DefaultMapper implements MapperInterface
{
    /**
     * DefaultMapping
     *
     * @var array
     */
    protected static $defaultMapping = [
        'properties' => [],
    ];

    /**
     * Creates the mapping
     *
     * @param  array $indexerConfiguration The toplevel configuration for one indexer
     * @return array
     */
    public static function getMapping($indexerConfiguration)
    {
        $mapping = self::$defaultMapping;

        $mapping['properties'][ExtconfService::getMetaFieldname()] = self::getMetaMapping();

        return $mapping;
    }

    /**
     * Returns the meta field mapping
     *
     * @return array
     */
    protected static function getMetaMapping()
    {

        return ["enabled" => false];
    }
}

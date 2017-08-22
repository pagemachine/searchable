<?php
namespace PAGEmachine\Searchable\Feature;

use PAGEmachine\Searchable\Configuration\DynamicConfigurationInterface;
use PAGEmachine\Searchable\Query\QueryInterface;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * AbstractFeature
 */
abstract class AbstractFeature implements DynamicConfigurationInterface
{
    /**
     * DefaultConfiguration
     * Add your own default configuration here if necessary
     *
     * @var array
     */
    protected static $defaultConfiguration = [];

    /**
     * This function will be called by the ConfigurationManager.
     * It can be used to add default configuration
     *
     * @param array $currentSubconfiguration The subconfiguration at this classes' level. This is the part that can be modified
     * @param array $parentConfiguration
     */
    public static function getDefaultConfiguration($currentSubconfiguration, $parentConfiguration)
    {
        return static::$defaultConfiguration;
    }

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @param array $config
     */
    public function __construct($config = null)
    {
        $this->config = $config;
    }

    /**
     * @var string
     */
    public static $featureName = "";

    /**
     * Returns the feature name (used in TypoScript to enable/disable the feature in FE)
     *
     * @return string
     */
    public static function getFeatureName()
    {
        return self::$featureName;
    }

    /**
     * Entry point to modify mapping.
     * Static to improve performance
     *
     * @param  array  $mapping
     * @param  array  $configuration
     * @return array  $mapping
     */
    public static function modifyMapping($mapping, $configuration)
    {
        return $mapping;
    }

    /**
     * Entry point to modify records before insert/update
     *
     * @param  array  $record
     * @return array  $record
     */
    public function modifyRecord($record)
    {
        return $record;
    }

    /**
     * Modifies a query before it is executed
     *
     * @param QueryInterface $query
     * @return array
     */
    public function modifyQuery(QueryInterface $query)
    {
        return $query;
    }
}

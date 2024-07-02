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
     * @param array $config
     */
    public function __construct(protected $config = null)
    {
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
     * @return QueryInterface
     */
    public function modifyQuery(QueryInterface $query)
    {
        return $query;
    }

    /**
     * Adds copy_to flag to field mapping
     *
     * @param array $fieldArray
     * @param array $mapping
     * @param string $copyToField
     */
    protected static function addRecursiveCopyTo($fieldArray, $mapping, $copyToField)
    {
        if (!empty($fieldArray)) {
            foreach ($fieldArray as $key => $field) {
                if (is_array($field)) {
                    $mapping['properties'][$key] = self::addRecursiveCopyTo($field, $mapping['properties'][$key] ?? [], $copyToField);
                } else {
                    $mapping['properties'][$field]['type'] = 'text';
                    if (!isset($mapping['properties'][$field]['copy_to'])) {
                        $mapping['properties'][$field]['copy_to'] = [];
                    }
                    $mapping['properties'][$field]['copy_to'][] = $copyToField;
                }
            }
        }

        return $mapping;
    }
}

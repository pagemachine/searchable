<?php
namespace PAGEmachine\Searchable\Configuration;

/*
 * This file is part of the Pagemachine Searchable project.
 */

interface DynamicConfigurationInterface
{
    /**
     * This function will be called by the ConfigurationManager.
     * It can be used to add default configuration
     *
     * @param array $currentSubconfiguration The subconfiguration at this classes' level. This is the part that can be modified
     * @param array $parentConfiguration
     */
    public static function getDefaultConfiguration($currentSubconfiguration, $parentConfiguration);
}

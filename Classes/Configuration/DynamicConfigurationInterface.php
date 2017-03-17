<?php
namespace PAGEmachine\Searchable\Configuration;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

interface DynamicConfigurationInterface {

    /**
     * This function will be called by the ConfigurationManager.
     * It can be used to add default configuration
     *
     * @param array $rootConfiguration The complete root configuration
     * @param array $currentSubconfiguration The subconfiguration at this classes' level. This is the part that can be modified
     */
    public static function getDefaultConfiguration($rootConfiguration, $currentSubconfiguration);    
}

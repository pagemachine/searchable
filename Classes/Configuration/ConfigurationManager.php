<?php
namespace PAGEmachine\Searchable\Configuration;

use PAGEmachine\Searchable\Configuration\DynamicConfigurationInterface;
use PAGEmachine\Searchable\Service\ConfigurationMergerService;
use PAGEmachine\Searchable\Service\ExtconfService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Builds and manages the complete indexer configuration
 */
class ConfigurationManager implements SingletonInterface {

    /**
     * @return ConfigurationManager
     */
    public static function getInstance() {

        return GeneralUtility::makeInstance(ConfigurationManager::class);

    }

    /**
     * Holds the processed configuration so it runs only once through the full stack of classes
     *
     * @var array
     */
    protected $processedConfiguration = null;

    /**
     * Builds and returns the processed configuration
     *
     * @return array
     */
    public function getIndexerConfiguration() {

        if ($this->processedConfiguration == null) {

            $configuration = ExtconfService::getInstance()->getIndexerConfiguration();

            foreach ($configuration as $key => $indexerConfiguration) {
                $configuration[$key] = $this->buildConfiguration($indexerConfiguration, $configuration);

            }

            $this->processedConfiguration = $configuration;

        }
        return $this->processedConfiguration;

    }

    /**
     * Builds configuration recursively by calling $subclass::getDefaultConfiguration if there is a subclass
     *
     * @param  array $configuration
     * @param  array $parentConfiguration
     * @return array
     */
    protected function buildConfiguration($configuration, $parentConfiguration) {

        if (is_string($configuration['className']) && !empty($configuration['className'])) {

            // Class will only be called if it implements a specific interface.
            // @todo should this throw an exception or is it legit to have classes without dynamic configuration?
            if (in_array(DynamicConfigurationInterface::class, class_implements($configuration['className']))) {

                $defaultConfiguration = $configuration['className']::getDefaultConfiguration($configuration['config'], $parentConfiguration);

                if (is_array($defaultConfiguration)) {

                    $configuration['config'] = ConfigurationMergerService::merge($defaultConfiguration, $configuration['config']);
                }                       
            }
        }

        if (!empty($configuration['config'])) {

            //Recursive calls to fetch additional data
            foreach($configuration['config'] as $key => $config) {

                if (is_array($config) && !empty($config)) {

                    $configuration['config'][$key] = $this->buildConfiguration($config, $configuration);
                }
            }            
        }

        return $configuration;
    }


}

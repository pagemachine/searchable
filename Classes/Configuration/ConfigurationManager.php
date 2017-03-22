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
     * UpdateConfiguration
     * @var array
     */
    protected $updateConfiguration = [
        'database' => []
    ];


    /**
     * Builds and returns the processed configuration
     *
     * @return array
     */
    public function getIndexerConfiguration() {

        if ($this->processedConfiguration == null) {

            $configuration = ExtconfService::getInstance()->getIndexerConfiguration();

            foreach ($configuration as $key => $indexerConfiguration) {
                $configuration[$key] = $this->processIndexerLevel($indexerConfiguration);

            }

            $this->processedConfiguration = $configuration;

        }
        return $this->processedConfiguration;
    }

    /**
     *
     * @param  array $indexerConfiguration
     * @return array
     */
    protected function processIndexerLevel($indexerConfiguration) {

        $indexerConfiguration = $this->addClassDefaultConfiguration($indexerConfiguration, []);

        if (!empty($indexerConfiguration['config'])) {

            if (!empty($indexerConfiguration['config']['collector'])) {

                $indexerConfiguration['config']['collector'] = $this->addRecursiveCollectorConfig($indexerConfiguration['config']['collector'], $indexerConfiguration);
            }

            if (!empty($indexerConfiguration['config']['preview'])) {

                $indexerConfiguration['config']['preview'] = $this->addClassDefaultConfiguration($indexerConfiguration['config']['preview'], $indexerConfiguration);
            }

            if (!empty($indexerConfiguration['config']['link'])) {

                $indexerConfiguration['config']['link'] = $this->addClassDefaultConfiguration($indexerConfiguration['config']['link'], $indexerConfiguration);
            }
        }

        return $indexerConfiguration;

    }

    /**
     * Adds collector configuration
     *
     * @param array $configuration
     * @param array $parentConfiguration
     */
    protected function addRecursiveCollectorConfig($configuration, $parentConfiguration) {

        $configuration = $this->addClassDefaultConfiguration($configuration, $parentConfiguration);

        if (!empty($configuration['config'])) {

            if (!empty($configuration['config']['resolver'])) {

                $configuration['config']['resolver'] =  $this->addClassDefaultConfiguration($configuration['config']['resolver'], $configuration);
            }

            if (!empty($configuration['config']['subCollectors'])) {

                foreach ($configuration['config']['subCollectors'] as $key => $subCollectorConfig) {

                    $configuration['config']['subCollectors'][$key] = $this->addRecursiveCollectorConfig($subCollectorConfig, $configuration);
                }
            }  
        }

        return $configuration;
    }

    /**
     * Adds class default configuration
     *
     * @param array $configuration
     * @param array $parentConfiguration
     */
    protected function addClassDefaultConfiguration($configuration, $parentConfiguration) {

        if (is_string($configuration['className']) && !empty($configuration['className'])) {

            // Class will only be called if it implements a specific interface.
            // @todo should this throw an exception or is it legit to have classes without dynamic configuration?
            if (in_array(DynamicConfigurationInterface::class, class_implements($configuration['className']))) {

                $defaultConfiguration = $configuration['className']::getDefaultConfiguration($configuration['config'], $parentConfiguration['config']);

                if (is_array($defaultConfiguration)) {

                    $configuration['config'] = $configuration['config'] ?: [];

                    $configuration['config'] = ConfigurationMergerService::merge($defaultConfiguration, $configuration['config']);
                }                       
            }
        }

        return $configuration;
    }

    /**
     * Returns an array containing all relevant tables for updating
     * This is basically an inverted array, flattening all subcollectors and connecting them to the toplevel parent 
     *
     * @return array
     */
    public function getUpdateConfiguration() {

        if ($this->processedConfiguration == null) {

            $this->getIndexerConfiguration();
        }

        return $this->updateConfiguration;
    }

}

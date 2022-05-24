<?php
namespace PAGEmachine\Searchable\Configuration;

use PAGEmachine\Searchable\Configuration\DynamicConfigurationInterface;
use PAGEmachine\Searchable\Feature\FeatureInterface;
use PAGEmachine\Searchable\Mapper\MapperInterface;
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
class ConfigurationManager implements SingletonInterface
{
    /**
     * @return ConfigurationManager
     */
    public static function getInstance()
    {
        return GeneralUtility::makeInstance(ConfigurationManager::class);
    }

    /**
     * Holds the processed configuration so it runs only once through the full stack of classes
     *
     * @var array
     */
    protected $processedConfiguration = null;

    /**
     * Holds the processed mapping for each index
     *
     * @var array
     */
    protected $processedMapping = null;

    /**
     * Holds the processed query configuration
     *
     * @var array
     */
    protected $processedQueryConfiguration = null;

    /**
     * UpdateConfiguration
     * @var array
     */
    protected $updateConfiguration = [
        'database' => [
            'toplevel' => [],
            'sublevel' => [],
        ],
    ];


    /**
     * Builds and returns the processed configuration
     *
     * @return array
     */
    public function getIndexerConfiguration()
    {
        if ($this->processedConfiguration == null) {
            $configuration = ExtconfService::getInstance()->getIndexerConfiguration();
            $mapping = [];

            foreach ($configuration as $key => $indexerConfiguration) {
                $type = $indexerConfiguration['config']['type'];

                $configuration[$key] = $this->processIndexerLevel($indexerConfiguration);
                $mapping[$type] = $configuration[$key]['config']['mapping'];
            }

            $this->processedConfiguration = $configuration;
            $this->processedMapping = $mapping;
        }
        return $this->processedConfiguration;
    }

    /**
     * Merges
     *
     * @param  string $index The index to pull the mapping from
     * @return array
     */
    public function getMapping($index)
    {
        if ($this->processedMapping == null) {
            $this->getIndexerConfiguration();
        }

        return $this->processedMapping;
    }

    /**
     * Returns the query configuration for a given query name
     *
     * @param string $queryClassName
     * @return array
     */
    public function getQueryConfiguration($queryClassName)
    {
        if ($this->processedQueryConfiguration == null) {
            $queryConfiguration = ExtconfService::getInstance()->getQueryConfiguration();

            foreach ($queryConfiguration as $queryName => $config) {
                if (!empty($config['features'])) {
                    foreach ($config['features'] as $key => $feature) {
                        $queryConfiguration[$queryName]['features'][$key] = $this->addClassDefaultConfiguration($feature, $queryConfiguration);
                    }
                }
            }

            $this->processedQueryConfiguration = $queryConfiguration;
        }

        return $this->processedQueryConfiguration[$queryClassName];
    }

    /**
     * Returns an array containing all relevant tables for updating
     * This is basically an inverted array, flattening all subcollectors and connecting them to the toplevel parent
     *
     * @return array
     */
    public function getUpdateConfiguration()
    {
        if ($this->processedConfiguration == null) {
            $this->getIndexerConfiguration();
        }
        return $this->updateConfiguration;
    }

    /**
     *
     * @param  array $indexerConfiguration
     * @return array
     */
    protected function processIndexerLevel($indexerConfiguration)
    {
        $indexerConfiguration = $this->addClassDefaultConfiguration($indexerConfiguration, []);

        if (!empty($indexerConfiguration['config'])) {
            if (!empty($indexerConfiguration['config']['collector'])) {
                $indexerConfiguration['config']['collector'] = $this->addRecursiveCollectorConfig($indexerConfiguration['config']['collector'], $indexerConfiguration, $indexerConfiguration['config']['type']);
            }

            if (!empty($indexerConfiguration['config']['preview'])) {
                $indexerConfiguration['config']['preview'] = $this->addClassDefaultConfiguration($indexerConfiguration['config']['preview'], $indexerConfiguration);
            }

            if (!empty($indexerConfiguration['config']['link'])) {
                $indexerConfiguration['config']['link'] = $this->addClassDefaultConfiguration($indexerConfiguration['config']['link'], $indexerConfiguration);
            }

            if (!empty($indexerConfiguration['config']['features'])) {
                foreach ($indexerConfiguration['config']['features'] as $key => $feature) {
                    $indexerConfiguration['config']['features'][$key] = $this->addClassDefaultConfiguration($feature, $indexerConfiguration);

                    if (in_array(FeatureInterface::class, class_implements($feature['className']))) {
                        $indexerConfiguration['config']['mapping'] = $feature['className']::modifyMapping(
                            $indexerConfiguration['config']['mapping'],
                            $indexerConfiguration['config']['features'][$key]['config']
                        );
                    }
                }
            }

            if (!empty($indexerConfiguration['config']['mapper'])) {
                $indexerConfiguration['config']['mapping'] = $this->addMapping($indexerConfiguration);
            }
        }

        return $indexerConfiguration;
    }

    /**
     * Adds class default configuration
     *
     * @param array $configuration
     * @param array $parentConfiguration
     */
    protected function addClassDefaultConfiguration($configuration, $parentConfiguration)
    {
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
     * Calls the defined mapper class to add mapping
     * @todo
     *
     * @param array $indexerConfiguration
     * @return array $mapping
     */
    protected function addMapping($indexerConfiguration)
    {
        $mapping = [];

        // Apply mapper
        if (is_string($indexerConfiguration['config']['mapper']['className']) && !empty($indexerConfiguration['config']['mapper']['className'])) {
            // Class will only be called if it implements a specific interface.
            // @todo should this throw an exception or is it legit to have classes without dynamic configuration?
            if (in_array(MapperInterface::class, class_implements($indexerConfiguration['config']['mapper']['className']))) {
                $mapping = ConfigurationMergerService::merge(
                    $indexerConfiguration['config']['mapper']['className']::getMapping($indexerConfiguration),
                    ($indexerConfiguration['config']['mapping'] ?: [])
                );
            }
        }

        return $mapping;
    }

    /**
     * Adds collector configuration
     *
     * @param array $configuration
     * @param array $parentConfiguration
     * @param string $typeName
     * @param string $collectorPath
     */
    protected function addRecursiveCollectorConfig($configuration, $parentConfiguration, $typeName, $collectorPath = "")
    {
        $configuration = $this->addClassDefaultConfiguration($configuration, $parentConfiguration);

        if (!empty($configuration['config'])) {
            if (!empty($configuration['config']['table'])) {
                if ($configuration['config']['field']) {
                    $collectorPath = $collectorPath ? $collectorPath . "." . $configuration['config']['field'] : $configuration['config']['field'];
                    $this->addSublevelUpdateConfiguration($typeName, $collectorPath, $configuration['config']['table']);
                } else {
                    $this->addToplevelUpdateConfiguration($typeName, $configuration['config']['table']);
                }
            }

            if (!empty($configuration['config']['resolver'])) {
                $configuration['config']['resolver'] =  $this->addClassDefaultConfiguration($configuration['config']['resolver'], $configuration);
            }

            if (!empty($configuration['config']['subCollectors'])) {
                foreach ($configuration['config']['subCollectors'] as $key => $subCollectorConfig) {
                    $configuration['config']['subCollectors'][$key] = $this->addRecursiveCollectorConfig($subCollectorConfig, $configuration, $typeName, $collectorPath);
                }
            }
            if (!empty($configuration['config']['features'])) {
                foreach ($configuration['config']['features'] as $key => $feature) {
                    $configuration['config']['features'][$key] = $this->addClassDefaultConfiguration($feature, $configuration);
                }
            }
        }

        return $configuration;
    }

    /**
     * Adds a path to toplevel update configuration
     *
     * @param string $typeName
     * @param string $table
     */
    protected function addToplevelUpdateConfiguration($typeName, $table)
    {
        $this->updateConfiguration['database']['toplevel'][$table][] = $typeName;
    }

    /**
     * Adds a path to sublevel update configuration
     *
     * @param string $typeName
     * @param string $sublevelPath
     * @param string $table
     */
    protected function addSublevelUpdateConfiguration($typeName, $sublevelPath, $table)
    {
        $this->updateConfiguration['database']['sublevel'][$table][$typeName] = $sublevelPath;
    }
}

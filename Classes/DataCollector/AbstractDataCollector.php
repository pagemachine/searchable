<?php
namespace PAGEmachine\Searchable\DataCollector;

use PAGEmachine\Searchable\Configuration\DynamicConfigurationInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * AbstractDataCollector
 */
abstract class AbstractDataCollector implements DynamicConfigurationInterface
{
    /**
     * @var array $defaultConfiguration
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
     * ObjectManager
     *
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     *
     * @param array $config
     */
    public function setConfig($config = [])
    {
        $this->config = $config;
    }


    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @return int
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param int $language
     * @return void
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * @var array $subCollectors
     */
    protected $subCollectors = [];

    /**
     * @return array
     */
    public function getSubCollectors()
    {
        return $this->subCollectors;
    }

    /**
     * @var array $features
     */
    protected $features = [];

    /**
     * Adds a new SubCollector for subtypes
     *
     * @param string                 $field        Fieldname to apply this collector to
     */
    public function addSubCollector($field, DataCollectorInterface $collector)
    {
        $this->subCollectors[$field] = $collector;
    }

    /**
     * Returns a subcollector by given field
     *
     * @param  string $field
     * @return DataCollectorInterface $subCollector
     */
    public function getSubCollectorForField($field)
    {
        if (!empty($this->subCollectors[$field]) && $this->subCollectors[$field] instanceof DataCollectorInterface) {
            return $this->subCollectors[$field];
        }

        throw new \Exception("Subcollector for field '" . $field . "' is not defined.", 1487341012);
    }

    /**
     * Returns true if a subcollector exists for given field
     *
     * @param  string $field
     * @return bool
     */
    public function subCollectorExists($field)
    {
        if (!empty($this->subCollectors[$field]) && $this->subCollectors[$field] instanceof DataCollectorInterface) {
            return true;
        }
        return false;
    }

    /**
     *
     * @param array $config
     * @param int $language
     */
    public function __construct(protected $config = [], protected $language = 0, ObjectManager $objectManager = null)
    {
        $this->objectManager = $objectManager ?: GeneralUtility::makeInstance(ObjectManager::class);

        if (!empty($this->config['features'])) {
            foreach ($this->config['features'] as $key => $featureConfig) {
                $this->features[$key] = $this->objectManager->get($featureConfig['className'], $featureConfig['config']);
            }
        }
    }

    /**
     * @return void
     */
    public function initializeObject()
    {
        $this->buildSubCollectors();
    }

    /**
     * Builds up subcollectors. Note that this function will be called in the subcollectors as well, so all collectors build a tree structure.
     *
     * @return void
     */
    public function buildSubCollectors()
    {
        $this->subCollectors = [];

        if (!empty($this->config['subCollectors'])) {
            foreach ($this->config['subCollectors'] as $key => $subtypeConfig) {
                $subtypeCollectorClass = $subtypeConfig['className'] ?: static::class;

                $subCollector = $this->buildSubCollector($subtypeCollectorClass, $subtypeConfig['config']);

                $this->addSubCollector($key, $subCollector);
            }
        }
    }

    /**
     * Builds a new subcollector
     * Override this method to do custom stuff to the new collector
     *
     * @param string $classname
     * @param  array  $collectorConfig
     * @return DataCollectorInterface
     */
    public function buildSubCollector($classname, $collectorConfig = [])
    {
        $subCollector = $this->objectManager->get($classname, $collectorConfig, $this->language);

        return $subCollector;
    }

    /**
     * Apply features to record
     *
     * @param array $record
     * @return array $record
     */
    protected function applyFeatures($record)
    {
        foreach ($this->features as $feature) {
            $record = $feature->modifyRecord($record);
        }
        return $record;
    }
}

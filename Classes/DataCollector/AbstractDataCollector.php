<?php
namespace PAGEmachine\Searchable\DataCollector;

use PAGEmachine\Searchable\Service\ConfigurationMergerService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * AbstractDataCollector
 */
abstract class AbstractDataCollector implements DataCollectorInterface {

	/**
	 * @var array $defaultConfiguration
	 */
	protected $defaultConfiguration = [];
	
	/**
	 * @return array
	 */
	public function getDefaultConfiguration() {
	  return $this->defaultConfiguration;
	}
	
	/**
	 * @param array $defaultConfiguration
	 * @return void
	 */
	public function setDefaultConfiguration($defaultConfiguration) {
	  $this->defaultConfiguration = $defaultConfiguration;
	}


	/**
	 * @var array $configuration
	 */
	protected $configuration = [];

	
	/**
	 * @return array
	 */
	public function getConfiguration() {
	  return $this->configuration;
	}

	/**
	 * @var array $subCollectors
	 */
	protected $subCollectors = [];
	
	/**
	 * @return array
	 */
	public function getSubCollectors() {
	  return $this->subCollectors;
	}
	
	/**
	 * Adds a new SubCollector for subtypes
	 *
	 * @param string                 $field        Fieldname to apply this collector to
	 * @param DataCollectorInterface $subCollector
	 */
	public function addSubCollector($field, DataCollectorInterface $collector) {

		$this->subCollectors[$field] = $collector;
	}

	/**
	 * Returns a subcollector by given field
	 *
	 * @param  string $field
	 * @return DataCollectorInterface $subCollector
	 */
	public function getSubCollectorForField($field) {

		if (!empty($this->subCollectors[$field]) && $this->subCollectors[$field] instanceof DataCollectorInterface) {

			return $this->subCollectors[$field];
		}

		throw new \Exception("Subcollector for field '" . $field . "' is not defined.", 1487341012);
	}

	/**
	 *
	 * @param array $configuration
	 */
	public function __construct($configuration = []) {

		$this->configuration = $this->buildConfiguration($configuration);

		$this->buildSubCollectors();
	}

	/**
	 * Builds configuration - hook into here if you want to add some stuff to config manually
	 *
	 * @param  array  $configuration
	 * @return array $mergedConfiguration
	 */
	public function buildConfiguration($configuration = []) {

		$mergedConfiguration = ConfigurationMergerService::merge($this->defaultConfiguration, $configuration);
		return $mergedConfiguration;

	}

	/**
	 * Builds up subcollectors. Note that this function will be called in the subcollectors as well, so all collectors build a tree structure.
	 *
	 * @return void
	 */
	public function buildSubCollectors() {

		$this->subCollectors = [];

		if (!empty($this->configuration['subtypes'])) {

			foreach ($this->configuration['subtypes'] as $subtypeConfig) {

				$subCollector = $this->buildSubCollector($subtypeConfig['collector'], $subtypeConfig['config']);

				$this->addSubCollector($subtypeConfig['config']['field'], $subCollector);
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
	public function buildSubCollector($classname, $collectorConfig = []) {

		$subCollector = GeneralUtility::makeInstance($classname, $collectorConfig);

		return $subCollector;

	}

}
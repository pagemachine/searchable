<?php
namespace PAGEmachine\Searchable\Query;

use Elasticsearch\Client;
use PAGEmachine\Searchable\Configuration\ConfigurationManager;
use PAGEmachine\Searchable\Connection;
use PAGEmachine\Searchable\Service\ExtconfService;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * This file is part of the Pagemachine Searchable project.
 */

/**
 * Abstract helper class for elasticsearch querying
 */
abstract class AbstractQuery implements QueryInterface
{
    /**
     * The array that is filled and later sent to the elasticsearch client for bulk indexing
     *
     * @var array $parameters
     */
    protected $parameters = [];

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param array $parameters
     * @return QueryInterface
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getParameter($key)
    {
        return $this->parameters[$key] ?? null;
    }

    /**
     * @param string $key
     * @return void
     */
    public function setParameter($key, mixed $parameter)
    {
        $this->parameters[$key] = $parameter;
    }

    /**
     * Array of strings with indices that should be used in the query. Can contain both ES index names and indexer names
     *
     * @var array $indices
     */
    protected $indices = [];

    /**
     * @param array $indices
     */
    public function setIndices(array $indices): void
    {
        $this->indices = $indices;
    }

    /**
     * @return array
     */
    public function getIndices(): array
    {
        return $this->indices;
    }

    /**
     * @param string $index
     */
    public function addIndex(string $index): static
    {
        $this->indices[] = $index;
        return $this;
    }

    /**
     * @param string $index
     */
    public function removeIndex(string $index): static
    {
        $this->indices = array_diff($this->indices, [$index]);
        return $this;
    }

    /**
     * Translates the indices to elasticsearch indices. Makes sure only active indices are used
     *
     * @return array
     */
    public function getElasticsearchIndices(): array
    {
        $indices = $this->getIndices();
        $activeIndices = $this->getActiveIndices();

        $esIndices = [];

        foreach ($activeIndices as $index) {
            $indexer = ExtconfService::getIndexerKeyOfIndex($index);
            if (in_array($index, $indices) || in_array($indexer, $indices)) {
                $esIndices[] = $index;
            }
        }

        return $esIndices;
    }

    /**
     * @return string
     */
    public function getTerm()
    {
        return '';
    }

    /**
     * @param string $term
     * @return QueryInterface
     */
    public function setTerm($term)
    {
        return $this;
    }

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var Logger
     */
    protected Logger $logger;

    /**
     * Features
     *
     * @var array
     */
    protected $features = [];

    /**
     * @var array $featureSettings
     */
    protected $featureSettings;

    /**
     * @return array
     */
    public function getFeatureSettings()
    {
        return $this->featureSettings;
    }

    /**
     * @param array $featureSettings
     * @return AbstractQuery
     */
    public function setFeatureSettings($featureSettings)
    {
        $this->featureSettings = $featureSettings;

        return $this;
    }


    /**
     * @var bool $pluginMode
     */
    protected $pluginMode = false;

    /**
     * @return bool
     */
    public function getPluginMode()
    {
        return $this->pluginMode;
    }

    /**
     * @param bool $pluginMode
     * @return AbstractQuery
     */
    public function setPluginMode($pluginMode)
    {
        $this->pluginMode = $pluginMode;

        return $this;
    }

    /**
     * @param Client|null $client
     * @param array $features
     */
    public function __construct(Client $client = null, $features = null)
    {
        $this->client = $client ?: Connection::getClient();
        $this->logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(self::class);

        // Use get_class() instead of static self::class to retrieve the inherited child classname
        $features = $features ?: ConfigurationManager::getInstance()->getQueryConfiguration(static::class)['features'] ?? [];

        if (!empty($features)) {
            foreach ($features as $key => $feature) {
                $this->features[$key] = GeneralUtility::makeInstance($feature['className'], $feature['config']);
            }
        }
    }

    /**
     * Execute method, should be overriden with the concrete command to the client
     * and return the response
     *
     * @return array
     */
    public function execute()
    {
        return [];
    }

    /**
     * Apply features to query
     *
     */
    protected function applyFeatures()
    {
        foreach ($this->features as $name => $feature) {
            if ($this->isFeatureEnabled($name)) {
                $feature->modifyQuery($this);
            }
        }
    }

    /**
     * Checks if a feature is enabled for this query.
     * This only applies if the query is in plugin mode (where the controller decides which feature to use).
     * Otherwise all features assigned in $_EXTCONF will be executed
     *
     * @param string  $featureName
     * @return bool
     */
    public function isFeatureEnabled($featureName)
    {
        if (!$this->pluginMode ||
            (isset($this->featureSettings[$featureName]) && $this->featureSettings[$featureName] == 1)
            ) {
            return true;
        }
        return false;
    }

    /**
     * Return all active indices
     *
     * @return array
     */
    protected function getActiveIndices(): array
    {
        return ExtconfService::getIndices();
    }
}

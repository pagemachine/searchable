<?php
namespace PAGEmachine\Searchable\Query;

use PAGEmachine\Searchable\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Abstract helper class for elasticsearch querying
 */
abstract class AbstractSearchQuery extends AbstractQuery implements QueryInterface
{
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
     * @param array $features
     */
    public function init($features = null)
    {
        // Use get_class() instead of static self::class to retrieve the inherited child classname
        $features = $features ?: ConfigurationManager::getInstance()->getQueryConfiguration(static::class)['features'] ?? [];

        if (!empty($features)) {
            foreach ($features as $key => $featureConfig) {
                $feature = GeneralUtility::makeInstance($featureConfig['className']);
                $feature->init($featureConfig['config']);
                $this->features[$key] = $feature;
            }
        }
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
}

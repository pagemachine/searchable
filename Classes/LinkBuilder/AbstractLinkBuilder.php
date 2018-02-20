<?php
namespace PAGEmachine\Searchable\LinkBuilder;

use PAGEmachine\Searchable\Configuration\DynamicConfigurationInterface;
use PAGEmachine\Searchable\Service\ConfigurationMergerService;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * AbstractLinkBuilder
 */
abstract class AbstractLinkBuilder implements DynamicConfigurationInterface
{
    /**
     * DefaultConfiguration
     * Add your own default configuration here if necessary
     *
     * @var array
     */
    protected static $defaultConfiguration = [
        'fixedParts' => [],
        'languageParam' => 'L',
    ];

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
     * @var array
     */
    protected $config = [];

    /**
     * @param array $config
     */
    public function __construct($config = null)
    {
        $this->config = $config;
    }

    /**
     * Creates merged link configuration
     *
     * @param  array $record
     * @param int $language
     * @return array
     */
    public function createLinkConfiguration($record, $language)
    {
        $linkConfiguration = $this->config['fixedParts'];

        $linkConfiguration = $this->addLanguageParameter($linkConfiguration, $language);

        if (!empty($this->config['dynamicParts'])) {
            $dynamicConfiguration = $this->replaceFieldsRecursive($this->config['dynamicParts'], $record);

            $linkConfiguration = ConfigurationMergerService::merge($linkConfiguration, $dynamicConfiguration);
        }

        return $linkConfiguration;
    }

    /**
     * Adds a language parameter to the link config
     *
     * @param array $linkConfiguration
     * @param int $language
     *
     * @return array
     */
    protected function addLanguageParameter($linkConfiguration, $language)
    {
        $linkConfiguration['additionalParams'][$this->config['languageParam']] = $language;
        return $linkConfiguration;
    }

    /**
     *
     * @param  array $configuration
     * @param  array $record
     * @return array
     */
    protected function replaceFieldsRecursive($configuration, $record)
    {
        foreach ($configuration as $key => $value) {
            if (is_array($value)) {
                $configuration[$key] = $this->replaceFieldsRecursive($value, $record);
            } elseif (is_string($value) && $record[$value] != null) {
                $configuration[$key] = $record[$value];
            } else {
                unset($configuration[$key]);
            }
        }

        return $configuration;
    }
}

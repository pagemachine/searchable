<?php
namespace PAGEmachine\Searchable\LinkBuilder;

use PAGEmachine\Searchable\Service\ConfigurationMergerService;


/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * PageLinkBuilder
 * Creates a link configuration array to be passed on to a Fluid link.page ViewHelper
 */
class PageLinkBuilder implements LinkBuilderInterface {

    /**
     * @var array
     */
    protected $config = [
        'titleField' => '',
        'fixedParts' => [
            'pageUid' => null,
            'additionalParams' => [],
            'pageType' => 0,
            'noCache' => false,
            'noCacheHash' => false,
            'section' => '',
            'linkAccessRestrictedPages' => false,
            'absolute' => false,
            'addQueryString' => false,
            'argumentsToBeExcludedFromQueryString' => [],
            'addQueryStringMethod' => null
        ],
        'dynamicParts' => [
        ]
    ];

    /**
     * @param array $config
     */
    public function __construct($config = null) {

        if ($config != null) {

            $this->config = ConfigurationMergerService::merge($this->config, $config);
        }     
    }

    /**
     * Creates a link
     * 
     * @param  array $record
     * @return array
     */
    public function createLinkConfiguration($record) {

        $linkConfiguration = $this->config['fixedParts'];

        if (!empty($this->config['dynamicParts'])) {

             $this->config['dynamicParts'] = $this->replaceFieldsRecursive($this->config['dynamicParts'], $record);

             $linkConfiguration = ConfigurationMergerService::merge($linkConfiguration, $this->config['dynamicParts']);
        }

        $linkConfiguration['title'] = $record[$this->config['titleField']];


        return $linkConfiguration;
    }

    /**
     *
     * @param  array $configuration
     * @param  array $record
     * @return array
     */
    protected function replaceFieldsRecursive($configuration, $record) {

        foreach ($configuration as $key => $value) {

            if (is_array($value)) {

                $configuration[$key] = $this->replaceFieldsRecursive($value, $record);
            } else if (is_string($value) && $record[$value] != null) {

                $configuration[$key] = $record[$value];
            } else {

                unset($configuration[$key]);
            }

        }

        return $configuration;
    }
}
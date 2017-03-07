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
            'pageUid' => '',
            'additionalParams' => []
        ]
    ];

    /**
     * @param array $config
     */
    public function __construct($config) {

        $this->config = ConfigurationMergerService::merge($this->config, $config);
    }

    /**
     * Creates a link
     * 
     * @param  array $record
     * @return array
     */
    public function createLink($record) {

        $linkConfiguration = $this->config['fixedParts'];

        foreach ($this->config['dynamicParts'] as $key => $partConfig) {

            if (!empty($partConfig)) {

                if (is_string($partConfig) && $record[$partConfig] != null) {

                    $linkConfiguration[$key] = $record[$partConfig];
                    continue;
                }
            }
        }

        return $linkConfiguration;
    }
}
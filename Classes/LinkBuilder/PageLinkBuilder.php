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
class PageLinkBuilder extends AbstractLinkBuilder implements LinkBuilderInterface {

    /**
     * The default title if the title field is empty
     *
     * @var string
     */
    protected $defaultTitle = "Link";

    /**
     * @var array
     */
    protected static $defaultConfiguration = [
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
     * Creates a link
     * 
     * @param  array $record
     * @return array
     */
    public function createLinkConfiguration($record) {

        $linkConfiguration = $this->config['fixedParts'];

        if (!empty($this->config['dynamicParts'])) {

            $dynamicConfiguration = $this->replaceFieldsRecursive($this->config['dynamicParts'], $record);

            $linkConfiguration = ConfigurationMergerService::merge($linkConfiguration, $dynamicConfiguration);
        }

        $linkConfiguration['title'] = $this->getLinkTitle($record);


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

    /**
     * Fetches the link title
     *
     * @param  array  $record
     * @return string
     */
    protected function getLinkTitle($record = []) {

        $title = $record[$this->config['titleField']];

        if ($title == null) {

            $title = $this->defaultTitle;
        }

        return $title;
    }
}

<?php
namespace PAGEmachine\Searchable\LinkBuilder;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * PageLinkBuilder
 * Creates a link configuration array to be passed on to a Fluid link.page ViewHelper
 */
class PageLinkBuilder extends AbstractLinkBuilder
{
    /**
     * @var array
     */
    protected static $defaultConfiguration = [
        'titleField' => '',
        'languageParam' => 'L',
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
            'addQueryStringMethod' => null,
        ],
        'dynamicParts' => [
        ],
    ];

    /**
     * Converts builder-specific configuration to TypoLink configuration
     *
     * @param  array $configuration
     * @param  array $record
     * @return array
     */
    public function convertToTypoLinkConfig($configuration, $record)
    {
        $linkConfiguration = $this->convertFromPageViewHelperConfig($configuration);

        return ['title' => $this->getLinkTitle($record), 'conf' => $linkConfiguration];
    }

    /**
     * Converts Link\PageViewHelper config to TypoLink config
     * @see \TYPO3\CMS\Extbase\Mvc\Web\Routing\UriBuilder::buildTypolinkConfiguration
     *
     * @param  array $configuration
     * @return array
     */
    protected function convertFromPageViewHelperConfig($configuration)
    {
        $typolinkConfiguration = [];

        $typolinkConfiguration['parameter'] = $configuration['pageUid'];

        if ($configuration['pageType'] !== 0) {
            $typolinkConfiguration['parameter'] .= ',' . $configuration['pageType'];
        }
        if (!empty($configuration['additionalParams'])) {
            $typolinkConfiguration['additionalParams'] = GeneralUtility::implodeArrayForUrl('', $configuration['additionalParams']);
        }

        if ($configuration['addQueryString'] === true) {
            $typolinkConfiguration['addQueryString'] = 1;

            if (!empty($configuration['argumentsToBeExcludedFromQueryString'])) {
                $typolinkConfiguration['addQueryString.'] = [
                    'exclude' => implode(',', $configuration['argumentsToBeExcludedFromQueryString']),
                ];
            }
            if ($configuration['addQueryStringMethod']) {
                $typolinkConfiguration['addQueryString.']['method'] = $configuration['addQueryStringMethod'];
            }
        }
        if ($configuration['noCache'] === true) {
            $typolinkConfiguration['no_cache'] = 1;
        } elseif ($configuration['useCacheHash']) {
            $typolinkConfiguration['useCacheHash'] = 1;
        }
        if ($configuration['section'] !== '') {
            $typolinkConfiguration['section'] = $configuration['section'];
        }
        if ($configuration['linkAccessRestrictedPages'] === true) {
            $typolinkConfiguration['linkAccessRestrictedPages'] = 1;
        }
        if ($configuration['absolute'] == true) {
            $typolinkConfiguration['forceAbsoluteUrl'] = 1;
        }
        return $typolinkConfiguration;
    }
}

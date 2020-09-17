<?php
namespace PAGEmachine\Searchable\LinkBuilder;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * TypoLinkBuilder
 * Creates a link based on typolink configuration
 */
class TypoLinkBuilder extends AbstractLinkBuilder
{
    /**
     * @var array
     */
    protected static $defaultConfiguration = [
        'titleField' => '',
        'languageParam' => 'L',
        'fixedParts' => [
            'parameter' => null,
            'additionalParams' => [],
        ],
        'dynamicParts' => [
        ],
    ];

    /**
     * @param array $configuration
     * @param array $record
     * @return array
     */
    public function finalizeTypoLinkConfig($configuration, $record)
    {
        if (!empty($configuration['additionalParams'])) {
            $configuration['additionalParams'] = GeneralUtility::implodeArrayForUrl('', $configuration['additionalParams']);
        }

        return $configuration;
    }
}

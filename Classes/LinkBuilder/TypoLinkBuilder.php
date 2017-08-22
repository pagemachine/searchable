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
class TypoLinkBuilder extends AbstractEidLinkBuilder implements LinkBuilderInterface
{
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
            'parameter' => null,
            'additionalParams' => [],
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
        if (!empty($configuration['additionalParams'])) {
            $configuration['additionalParams'] = GeneralUtility::implodeArrayForUrl(null, $configuration['additionalParams']);
        }

        return ['title' => $this->getLinkTitle($record), 'conf' => $configuration];
    }
}

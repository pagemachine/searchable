<?php
namespace PAGEmachine\Searchable\LinkBuilder;

use PAGEmachine\Searchable\Service\ConfigurationMergerService;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * NewsLinkBuilder
 * Creates a link based on typolink configuration keeping news types in mind
 */
class NewsLinkBuilder extends TypoLinkBuilder
{
    /**
     * @var array
     */
    protected static $defaultConfiguration = [
        'singlePage' => null,
        'titleField' => 'title',
        'type' => [
            0 => [
                'fixedParts' => [
                    'parameter' => null,
                    'additionalParams' => [
                        'tx_news_pi1' => [
                            'action' => 'detail',
                            'controller' => 'News',
                        ],
                    ],
                ],
                'dynamicParts' => [
                    'additionalParams' => [
                        'tx_news_pi1' => [
                            'news' => 'uid',
                        ],
                    ],
                ],
            ],
            1 => [
                'dynamicParts' => [
                    'parameter' => 'internalurl',
                ],
            ],
            2 => [
                'dynamicParts' => [
                    'parameter' => 'externalurl',
                ],
            ],
        ],
        'languageParam' => 'L',
        'fixedParts' => [
            'parameter' => null,
            'additionalParams' => [],
        ],
        'dynamicParts' => [],
    ];

    protected $configBackup;

    /**
     * Creates merged link configuration
     *
     * @param  array $record
     * @param int $language
     * @return array
     */
    public function createLinkConfiguration($record, $language)
    {
        if (!$this->configBackup) {
            $this->configBackup = $this->config;
        }

        $this->config = $this->configBackup;

        $this->config['type'][0]['fixedParts']['parameter'] = $this->config['singlePage'];

        $this->config = ConfigurationMergerService::merge($this->config, $this->config['type'][$record['type'][0]]);

        unset($this->config['type']);

        return parent::createLinkConfiguration($record, $language);
    }
}

<?php
namespace PAGEmachine\Searchable\LinkBuilder;

use PAGEmachine\Searchable\Configuration\DynamicConfigurationInterface;
use PAGEmachine\Searchable\Service\ExtconfService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * AbstractEidLinkBuilder
 */
abstract class AbstractEidLinkBuilder extends AbstractLinkBuilder implements DynamicConfigurationInterface
{
    /**
     * DefaultConfiguration
     * Add your own default configuration here if necessary
     *
     * @var array
     */
    protected static $defaultConfiguration = [
        'titleField' => 'title',
        'fixedParts' => [],
        'languageParam' => 'L',
    ];

    /**
     * The default title if the title field is empty
     *
     * @var string
     */
    protected $defaultTitle = "Link";

    /**
     * Creates links for a batch of records
     *
     * @param  array $records
     * @param int $language
     * @return array $records
     */
    public function createLinksForBatch($records, $language = 0)
    {
        $configurationArray = [];
        $metaField = ExtconfService::getInstance()->getMetaFieldname();

        foreach ($records as $key => $record) {
            $linkConfiguration = $this->createLinkConfiguration($record, $language);
            $linkConfiguration = $this->convertToTypoLinkConfig($linkConfiguration, $record);

            $configurationArray[$key] = $linkConfiguration;
        }

        $links = $this->getFrontendLinks($configurationArray);

        foreach ($links as $key => $link) {
            $records[$key][$metaField]['renderedLink'] = $link;
            $records[$key][$metaField]['linkTitle'] = $this->getLinkTitle($records[$key]);
        }

        return $records;
    }

    /**
     * Converts builder-specific configuration to TypoLink configuration
     * This should be overridden with custom conversion logic
     *
     * @param  array $configuration
     * @param  array $record
     * @return array
     */
    public function convertToTypoLinkConfig($configuration, $record)
    {
        return ['title' => $this->getLinkTitle($record), 'conf' => $configuration];
    }

    public function getFrontendLinks($configuration)
    {
        $domain = ExtconfService::getInstance()->getFrontendDomain();

        return $this->doRequest($domain, $configuration);
    }

    /**
     * The actual request
     *
     * @param  string $domain
     * @param  array $configuration
     * @return array
     */
    public function doRequest($domain, $configuration)
    {
        $requestFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Http\RequestFactory::class);
        $response = $requestFactory->request(
            $domain,
            'POST',
            [
                'query' => [
                    'eID' => 'searchable_linkbuilder',
                ],
                'form_params' => [
                    'configuration' => $configuration,
                ],
                'body' => $body ?: '',
                'http_errors' => false,
            ]
        );

        return json_decode($response->getBody()->getContents(), true);
    }

    /**
     * Fetches the link title
     *
     * @param  array  $record
     * @return string
     */
    protected function getLinkTitle($record = [])
    {
        $title = $record[$this->config['titleField']];

        if ($title == null) {
            $title = $this->defaultTitle;
        }

        return $title;
    }
}

<?php
namespace PAGEmachine\Searchable\LinkBuilder;

use PAGEmachine\Searchable\Configuration\DynamicConfigurationInterface;
use PAGEmachine\Searchable\Service\ConfigurationMergerService;
use PAGEmachine\Searchable\Service\ExtconfService;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Typolink\LinkFactory;
use TYPO3\CMS\Frontend\Typolink\UnableToLinkException;

/*
 * This file is part of the Pagemachine Searchable project.
 */

/**
 * AbstractLinkBuilder
 */
abstract class AbstractLinkBuilder implements LinkBuilderInterface, DynamicConfigurationInterface
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
    protected $defaultTitle = 'Link';

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
     * @param array $config
     */
    public function __construct(protected $config = null)
    {
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
     * Creates links for a batch of records
     *
     * @param array $records
     * @param int $language
     * @return array $records
     */
    public function createLinksForBatch($records, $language = 0)
    {
        $metaField = ExtconfService::getInstance()->getMetaFieldname();
        $finalRecords = [];

        foreach ($records as $key => $record) {
            $linkConfiguration = $this->createLinkConfiguration($record, $language);
            $linkConfiguration = $this->finalizeTypoLinkConfig($linkConfiguration, $record);

            try {
                $link = $this->getLink($linkConfiguration);
                $record[$metaField]['renderedLink'] = $link;
                $record[$metaField]['linkTitle'] = $this->getLinkTitle($record);
                $finalRecords[$key] = $record;
            } catch (UnableToLinkException $e) {
                $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(self::class);
                $logger->warning('Record with UID ' . ($record['uid'] ?? 'unknown') . ' skipped - ' . $e->getMessage());
            }
        }

        return $finalRecords;
    }

    /**
     * Converts builder-specific configuration to TypoLink configuration
     * This should be overridden with custom conversion logic
     *
     * @param array $configuration
     * @param array $record
     * @return array
     */
    public function finalizeTypoLinkConfig($configuration, $record)
    {
        return $configuration;
    }

    /**
     * Fetches the link title
     *
     * @param  array  $record
     * @return string
     */
    protected function getLinkTitle($record = [])
    {
        $title = $record[$this->config['titleField']] ?? null;

        if ($title == null) {
            $title = $this->defaultTitle;
        }

        return $title;
    }

    /**
     * Adds a language parameter to the link config for translations
     *
     * @param array $linkConfiguration
     * @param int $language
     *
     * @return array
     */
    protected function addLanguageParameter($linkConfiguration, $language)
    {
        if ($language > 0) {
            $linkConfiguration['additionalParams'][$this->config['languageParam']] = $language;
        }

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
            } elseif (is_string($value) && ($record[$value] ?? null) != null) {
                $configuration[$key] = $record[$value];
            } else {
                unset($configuration[$key]);
            }
        }

        return $configuration;
    }

    protected function getLink($configuration): string
    {
        if (!$configuration['parameter']) {
            return '';
        }

        $linkFactory = GeneralUtility::makeInstance(LinkFactory::class);
        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        $linkResult = $linkFactory->create('', $configuration, $contentObjectRenderer);
        return $linkResult->getUrl();
    }
}

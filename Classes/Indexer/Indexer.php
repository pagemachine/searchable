<?php
namespace PAGEmachine\Searchable\Indexer;

use PAGEmachine\Searchable\Configuration\DynamicConfigurationInterface;
use PAGEmachine\Searchable\LinkBuilder\LinkBuilderInterface;
use PAGEmachine\Searchable\LinkBuilder\PageLinkBuilder;
use PAGEmachine\Searchable\Preview\DefaultPreviewRenderer;
use PAGEmachine\Searchable\Preview\PreviewRendererInterface;
use PAGEmachine\Searchable\Preview\RequestAwarePreviewRendererInterface;
use PAGEmachine\Searchable\Query\BulkQuery;
use PAGEmachine\Searchable\Query\UpdateQuery;
use PAGEmachine\Searchable\Service\ExtconfService;
use PAGEmachine\Searchable\Utility\TsfeUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * This file is part of the Pagemachine Searchable project.
 */

class Indexer implements IndexerInterface, DynamicConfigurationInterface
{
    /**
     * DefaultConfiguration
     * Add your own default configuration here if necessary
     *
     * @var array
     */
    protected static $defaultConfiguration = [];

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
     * @var BulkQuery
     */
    protected $query;

    /**
     * @var \PAGEmachine\Searchable\DataCollector\DataCollectorInterface
     */
    protected $dataCollector;

    /**
     * @var PreviewRendererInterface
     */
    protected $previewRenderer;

    /**
     * @var LinkBuilderInterface
     */
    protected $linkBuilder;

    /**
     * @var array
     */
    protected $features = [];

    /**
     * @return String
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @param String $index
     * @return void
     */
    public function setIndex($index)
    {
        $this->index = $index;
    }


    /**
     * @var String $type
     */
    protected $type;

    /**
     * @return String
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param String $type
     * @return void
     */
    public function setType($type)
    {
        $this->type = $type;
    }

    /**
     * @return int
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param int $language
     * @return void
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * @param array $config
     * @return void
     */
    public function setConfig($config)
    {
        $this->config = $config;
    }

    /**
     * @param string      $index  The index name to use
     * @param int         $language The language uid to index
     * @param array      $config   The configuration to apply
     * @param BulkQuery|null $query
     * @param PreviewRendererInterface|null $previewRenderer
     * @param LinkBuilderInterface|null $linkBuilder
     * @param array       $features
     */
    public function __construct(protected $index, protected $language, protected $config = [], BulkQuery $query = null, PreviewRendererInterface $previewRenderer = null, LinkBuilderInterface $linkBuilder = null, $features = null)
    {
        $this->type = $this->config['type'] ?? null;

        if (empty($this->type)) {
            throw new \Exception('No type set in config for indexer ' . $index, 1669133301);
        }

        $this->dataCollector = GeneralUtility::makeInstance($this->config['collector']['className'], $this->config['collector']['config'], $this->language);

        $this->query = $query ?: new BulkQuery(
            $this->index,
            $this->config['pipeline'] ?? null
        );

        $this->setPreviewRenderer($previewRenderer);
        $this->setLinkBuilder($linkBuilder);
        $this->setFeatures($features);
    }

    /**
     * Sets the preview renderer
     *
     * @param PreviewRendererInterface|null $previewRenderer
     */
    protected function setPreviewRenderer(PreviewRendererInterface $previewRenderer = null)
    {
        if ($previewRenderer) {
            $this->previewRenderer = $previewRenderer;
        } else {
            if (isset($this->config['preview'])) {
                if (!empty($this->config['preview']['className'])) {
                    $this->previewRenderer = GeneralUtility::makeInstance($this->config['preview']['className'], $this->config['preview']['config']);
                } else {
                    $this->previewRenderer = GeneralUtility::makeInstance(DefaultPreviewRenderer::class, $this->config['preview']['config']);
                }
            }
        }
    }

    /**
     * Sets the link builder
     *
     * @param LinkBuilderInterface|null $linkBuilder
     */
    protected function setLinkBuilder(LinkBuilderInterface $linkBuilder = null)
    {
        if ($linkBuilder) {
            $this->linkBuilder = $linkBuilder;
        } else {
            if (isset($this->config['link'])) {
                if (!empty($this->config['link']['className'])) {
                    $this->linkBuilder = GeneralUtility::makeInstance($this->config['link']['className'], $this->config['link']['config']);
                } else {
                    $this->linkBuilder = GeneralUtility::makeInstance(PageLinkBuilder::class, $this->config['link']['config']);
                }
            }
        }
    }

    /**
     * Stores available features
     *
     * @param array $features
     */
    protected function setFeatures($features)
    {
        $features = $features ?: $this->config['features'] ?? [];

        if (!empty($features)) {
            foreach ($features as $key => $featureConfig) {
                $this->features[$key] = GeneralUtility::makeInstance($featureConfig['className'], $featureConfig['config']);
            }
        }
    }

    /**
     * Calls the specific classes for fields like preview and link
     *
     * @param array $record
     */
    protected function addSystemFields($record = [])
    {
        $systemFields = [];

        $systemFields['preview'] = $this->previewRenderer->render($record);

        $record[ExtconfService::getMetaFieldname()] = $systemFields;

        return $record;
    }

    /**
     * Main function for indexing
     *
     * @return \Generator
     */
    public function run()
    {
        TsfeUtility::createTSFE($this->config['siteIdentifier'] ?? null, $this->language);

        if (is_a($this->previewRenderer, RequestAwarePreviewRendererInterface::class)) {
            $this->previewRenderer->setRequest($GLOBALS['TYPO3_REQUEST']);
        }

        $bulkSize = ($this->config['bulkSize'] ?? null) ?: 20;

        $counter = 0;
        $overallCounter = 0;

        $records = [];

        foreach ($this->dataCollector->getRecords() as $fullRecord) {
            //@TODO: move to data collectors and add "yield from []" at the end there as soon as PHP7 is a requirement
            if (empty($fullRecord)) {
                continue;
            }

            $records[] = $this->addSystemFields($fullRecord);

            $counter++;
            $overallCounter++;

            if ($counter >= $bulkSize) {
                $this->sendBatch($records);

                $counter = 0;
                $records = [];
                yield $overallCounter;
            }
        }

        if ($counter != 0) {
            $this->sendBatch($records);

            yield $overallCounter;
        }
    }

    /**
     * Runs an update
     *
     * @return \Generator
     */
    public function runUpdate()
    {
        TsfeUtility::createTSFE($this->config['siteIdentifier'] ?? null, $this->language);

        if (is_a($this->previewRenderer, RequestAwarePreviewRendererInterface::class)) {
            $this->previewRenderer->setRequest($GLOBALS['TYPO3_REQUEST']);
        }

        $bulkSize = ($this->config['bulkSize'] ?? null) ?: 20;

        $counter = 0;
        $overallCounter = 0;

        $updateQuery = new UpdateQuery();

        $updates = $updateQuery->getUpdates($this->index, $this->type);

        $records = [];

        if (!empty($updates)) {
            foreach ($this->dataCollector->getUpdatedRecords($updates) as $fullRecord) {
                if ($fullRecord['deleted'] ?? 0 == 1) {
                    $this->query->delete($fullRecord['uid']);
                } else {
                    $counter++;
                    $overallCounter++;

                    $records[] = $this->addSystemFields($fullRecord);

                    if ($counter >= $bulkSize) {
                        $this->sendBatch($records);

                        $records = [];
                        $counter = 0;
                        yield $overallCounter;
                    }
                }
            }

            if ($counter != 0) {
                $this->sendBatch($records);

                yield $overallCounter;
            }
        }
    }

    /**
     * Sends a batch
     *
     * @param  array $records
     * @return void
     */
    protected function sendBatch($records)
    {
        $records = $this->linkBuilder->createLinksForBatch($records, $this->getLanguage());

        $this->query->addRows('uid', $records);

        $this->query->execute();
        $this->query->resetBody();
    }
}

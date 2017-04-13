<?php
namespace PAGEmachine\Searchable\Indexer;

use PAGEmachine\Searchable\Configuration\DynamicConfigurationInterface;
use PAGEmachine\Searchable\DataCollector\DataCollectorInterface;
use PAGEmachine\Searchable\LinkBuilder\LinkBuilderInterface;
use PAGEmachine\Searchable\LinkBuilder\PageLinkBuilder;
use PAGEmachine\Searchable\Mapper\DefaultMapper;
use PAGEmachine\Searchable\Mapper\MapperInterface;
use PAGEmachine\Searchable\Preview\DefaultPreviewRenderer;
use PAGEmachine\Searchable\Preview\PreviewRendererInterface;
use PAGEmachine\Searchable\Query\BulkQuery;
use PAGEmachine\Searchable\Query\UpdateQuery;
use PAGEmachine\Searchable\Service\ExtconfService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

class Indexer implements IndexerInterface, DynamicConfigurationInterface {

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
    public static function getDefaultConfiguration($currentSubconfiguration, $parentConfiguration) {

       return static::$defaultConfiguration;
    }

    /**
     * ObjectManager
     *
     * @var ObjectManager
     */
    protected $objectManager;


    /**
     * @var String $index
     */
    protected $index;

    /**
     * @var BulkQuery
     */
    protected $query;

    /**
     * @var DataCollectorInterface
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
     * @var MapperInterface
     */
    protected $mapper;
    
    /**
     * @return String
     */
    public function getIndex() {
      return $this->index;
    }
    
    /**
     * @param String $index
     * @return void
     */
    public function setIndex($index) {
      $this->index = $index;
    }


    /**
     * @var String $type
     */
    protected $type;
    
    /**
     * @return String
     */
    public function getType() {
      return $this->type;
    }
    
    /**
     * @param String $type
     * @return void
     */
    public function setType($type) {
      $this->type = $type;
    }


    /**
     * @var int $language
     */
    protected $language;
    
    /**
     * @return int
     */
    public function getLanguage() {
      return $this->language;
    }
    
    /**
     * @param int $language
     * @return void
     */
    public function setLanguage($language) {
      $this->language = $language;
    }


    /**
     * @var array $config
     */
    protected $config;
    
    /**
     * @return array
     */
    public function getConfig() {
      return $this->config;
    }
    
    /**
     * @param array $config
     * @return void
     */
    public function setConfig($config) {
      $this->config = $config;
    }

    /**
     * @param String      $index  The index name to use
     * @param int         $language The language uid to index
     * @param String      $type   The type to use
     * @param array      $config   The configuration to apply
     * @param BulkQuery|null $query
     * @param ObjectManager|null $objectManager
     * @param PreviewRendererInterface|null $previewRenderer
     */
    public function __construct($index, $language, $config = [], BulkQuery $query = null, ObjectManager $objectManager = null, PreviewRendererInterface $previewRenderer = null, LinkBuilderInterface $linkBuilder = null, MapperInterface $mapper = null) {

        $this->index = $index;
        $this->config = $config;
        $this->language = $language;

        $this->type = $this->config['type'];

        $this->objectManager = $objectManager?: GeneralUtility::makeInstance(ObjectManager::class);

        $this->dataCollector = $this->objectManager->get($this->config['collector']['className'], $this->config['collector']['config'], $this->language);

        $this->query = $query ?: new BulkQuery($this->index, $this->type);

        $this->setPreviewRenderer($previewRenderer);
        $this->setLinkBuilder($linkBuilder);

        $this->mapper = $mapper ?: DefaultMapper::getInstance();
         
    }

    /**
     * Returns the mapping for this indexer
     *
     * @return array
     */
    public function getMapping() {

        return $this->mapper->createMapping($this);
    }

    /**
     * Sets the preview renderer
     * 
     * @param PreviewRendererInterface|null $previewRenderer
     */
    protected function setPreviewRenderer(PreviewRendererInterface $previewRenderer = null) {

        if ($previewRenderer) {

            $this->previewRenderer = $previewRenderer;
        } else {

            if (!empty($this->config['preview']['className'])) {

                $this->previewRenderer = $this->objectManager->get($this->config['preview']['className'], $this->config['preview']['config']);
            } else {

                $this->previewRenderer = $this->objectManager->get(DefaultPreviewRenderer::class, $this->config['preview']['config']);
            }
        }


    }

    /**
     * Sets the link builder
     * 
     * @param LinkBuilderInterface|null $linkBuilder
     */
    protected function setLinkBuilder(LinkBuilderInterface $linkBuilder = null) {

        if ($linkBuilder) {

            $this->linkBuilder = $linkBuilder;
        } else {

            if (!empty($this->config['link']['className'])) {

                $this->linkBuilder = $this->objectManager->get($this->config['link']['className'], $this->config['link']['config']);
            } else {

                $this->linkBuilder = $this->objectManager->get(PageLinkBuilder::class, $this->config['link']['config']);
            }
        }

    }

    /**
     * Calls the specific classes for fields like preview and link
     *
     * @param array $record
     */
    protected function addSystemFields($record = []) {
        $systemFields = [];

        $systemFields['link'] = $this->linkBuilder->createLinkConfiguration($record);
        $systemFields['preview'] = $this->previewRenderer->render($record);

        $record[ExtconfService::getMetaFieldname()] = $systemFields;

        return $record;
    }

    /**
     * Main function for indexing
     * 
     * @return \Generator
     */
    public function run() {

        $counter = 0;
        $overallCounter = 0;

        foreach ($this->dataCollector->getRecords() as $fullRecord) {

            $fullRecord = $this->addSystemFields($fullRecord);

            $this->query->addRow($fullRecord['uid'], $fullRecord);

            $counter++;
            $overallCounter++;

            if ($counter >= 20) {

                $this->query->execute();
                $this->query->resetBody();

                $counter = 0;
                yield $overallCounter;
            }
        }

        if ($counter != 0) {

            $this->query->execute();
            $this->query->resetBody();
            yield $overallCounter;
        }

    }

    /**
     * Runs an update
     * 
     * @return \Generator
     */
    public function runUpdate() {

        $counter = 0;
        $overallCounter = 0;

        $updateQuery = new UpdateQuery();

        $updates = $updateQuery->getUpdates($this->index, $this->type);

        if (!empty($updates)) {
            foreach ($this->dataCollector->getUpdatedRecords($updates) as $fullRecord) {

                if ($fullRecord['deleted'] == 1) {

                    $this->query->delete($fullRecord['uid']);
                }
                else {

                    $counter++;
                    $overallCounter++;

                    $fullRecord = $this->addSystemFields($fullRecord);
                    $this->query->addRow($fullRecord['uid'], $fullRecord);

                    if ($counter >= 20) {

                        $this->query->execute();
                        $this->query->resetBody();

                        $counter = 0;
                        yield $overallCounter;
                    }
                }
            }

            if ($counter != 0) {

                $this->query->execute();
                $this->query->resetBody();
                yield $overallCounter;
            }
        }
    }
}

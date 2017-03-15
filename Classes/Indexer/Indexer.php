<?php
namespace PAGEmachine\Searchable\Indexer;

use PAGEmachine\Searchable\LinkBuilder\LinkBuilderInterface;
use PAGEmachine\Searchable\LinkBuilder\PageLinkBuilder;
use PAGEmachine\Searchable\Mapper\DefaultMapper;
use PAGEmachine\Searchable\Mapper\MapperInterface;
use PAGEmachine\Searchable\Preview\DefaultPreviewRenderer;
use PAGEmachine\Searchable\Preview\PreviewRendererInterface;
use PAGEmachine\Searchable\Query\BulkQuery;
use PAGEmachine\Searchable\Service\ConfigurationMergerService;
use PAGEmachine\Searchable\Service\ExtconfService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

class Indexer {

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
        $this->language = $language;

        if (!empty($config)) {
            $this->config = ConfigurationMergerService::merge($this->config, $config);
        }

        $this->type = $this->config['type'];

        $this->query = $query ?: new BulkQuery($this->index, $this->type);

        $this->objectManager = $objectManager?: GeneralUtility::makeInstance(ObjectManager::class);

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

            if (!empty($this->config['preview']['renderer'])) {

                $this->previewRenderer = $this->objectManager->get($this->config['preview']['renderer'], $this->config['preview']['config']);
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

            if (!empty($this->config['link']['builder'])) {

                $this->linkBuilder = $this->objectManager->get($this->config['link']['builder'], $this->config['link']['config']);
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
}

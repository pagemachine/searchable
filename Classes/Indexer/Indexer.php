<?php
namespace PAGEmachine\Searchable\Indexer;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use PAGEmachine\Searchable\Service\ConfigurationMergerService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

class Indexer {

    /**
     * Elasticsearch client
     * @var Client
     */
    protected $client;

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
     * @param String      $type   The type to use
     * @param array      $config   The configuration to apply
     * @param Client|null $client
     * @param ObjectManager|null $objectManager
     */
    public function __construct($index, $config = [], Client $client = null, ObjectManager $objectManager = null) {

        $this->index = $index;

        if (!empty($config)) {
            $this->config = ConfigurationMergerService::merge($this->config, $config);
        }

        $this->type = $this->config['type'];

        $this->client = $client ?: ClientBuilder::create()->build();

        $this->objectManager = $objectManager?: GeneralUtility::makeInstance(ObjectManager::class);
    }


}

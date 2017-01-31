<?php
namespace PAGEmachine\Searchable\Indexer;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;

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
     * @param String      $index  The index name to use
     * @param String      $type   The type to use
     * @param Client|null $client
     */
    public function __construct($index, $type, Client $client = null) {

        $this->index = $index;
        $this->type = $type;

        $this->client = $client ?: ClientBuilder::create()->build();
    }


}

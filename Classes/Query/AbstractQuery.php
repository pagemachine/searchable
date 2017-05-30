<?php
namespace PAGEmachine\Searchable\Query;

use Elasticsearch\Client;
use PAGEmachine\Searchable\Connection;
use TYPO3\CMS\Core\Log\Logger;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Abstract helper class for elasticsearch querying
 */
abstract class AbstractQuery {

    /**
     * The array that is filled and later sent to the elasticsearch client for bulk indexing
     * 
     * @var array $parameters
     */
    protected $parameters = [];
    
    /**
     * @return array
     */
    public function getParameters() {
      return $this->parameters;
    }
    
    /**
     * @param array $parameters
     * @return void
     */
    public function setParameters($parameters) {
      $this->parameters = $parameters;
    }
    
    /**
     * @param string $key
     * @return mixed
     */
    public function getParameter($key)
    {
        return isset($this->parameters[$key]) ? $this->parameters[$key] : null;
    }
    
    /**
     * @param string $key
     * @param mixed $parameter
     * @return void
     */
    public function setParameter($key, $parameter)
    {
        $this->parameters[$key] = $parameter;
    }

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @param Client|null $client
     * @param Logger|null $logger
     */
    public function __construct(Client $client = null, Logger $logger = null) {

        $this->client = $client ?: Connection::getClient();
        $this->logger = $logger ?: GeneralUtility::makeInstance(\TYPO3\CMS\Core\Log\LogManager::class)->getLogger(__CLASS__);

    }

    /**
     * Execute method, should be overriden with the concrete command to the client
     * and return the response
     * 
     * @return array
     */
    public function execute() {

        return [];
    }

}

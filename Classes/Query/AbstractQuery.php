<?php
namespace PAGEmachine\Searchable\Query;

use Elasticsearch\Client;
use PAGEmachine\Searchable\Connection;
use Psr\Log\LoggerInterface;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Abstract helper class for elasticsearch querying
 */
abstract class AbstractQuery
{
    /**
     * The array that is filled and later sent to the elasticsearch client for bulk indexing
     *
     * @var array $parameters
     */
    protected $parameters = [];

    /**
     * @return array
     */
    public function getParameters()
    {
        return $this->parameters;
    }

    /**
     * @param array $parameters
     * @return QueryInterface
     */
    public function setParameters($parameters)
    {
        $this->parameters = $parameters;

        return $this;
    }

    /**
     * @param string $key
     * @return mixed
     */
    public function getParameter($key)
    {
        return $this->parameters[$key] ?? null;
    }

    /**
     * @param string $key
     * @param mixed $parameter
     * @return void
     */
    public function setParameter($key, mixed $parameter)
    {
        $this->parameters[$key] = $parameter;
    }

    /**
     * @var Client
     */
    protected $client;

    /**
     * @param Client|null $client
     * @param Logger|null $logger
     */
    public function __construct(protected readonly LoggerInterface $logger, Client $client = null)
    {
        $this->client = $client ?: Connection::getClient();
    }

    /**
     * Execute method, should be overriden with the concrete command to the client
     * and return the response
     *
     * @return array
     */
    public function execute()
    {
        return [];
    }
}

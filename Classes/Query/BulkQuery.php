<?php
namespace PAGEmachine\Searchable\Query;

use Elasticsearch\Client;
use Psr\Log\LoggerInterface;
use PAGEmachine\Searchable\Connection;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Helper class to build up the parameter array for bulk indexing
 */
class BulkQuery
{
    /**
     * @var string $index
     */
    protected $index;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @return string
     */
    public function getIndex()
    {
        return $this->index;
    }

    /**
     * @param string $index
     * @return void
     */
    public function setIndex($index)
    {
        $this->index = $index;
    }


    /**
     * @var string $type
     */
    protected $type;

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     * @return void
     */
    public function setType(string $type)
    {
        $this->type = $type;
    }


    /**
     * @var string $pipeline
     */
    protected $pipeline;

    /**
     * @return string
     */
    public function getPipeline()
    {
        return $this->pipeline;
    }

    /**
     * @param string $pipeline
     * @return void
     */
    public function setPipeline($pipeline)
    {
        $this->pipeline = $pipeline;
    }

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
     * @param string $index
     * @param string $type
     */
    public function __construct(protected readonly LoggerInterface $logger, Client $client = null)
    {
        $this->client = $client ?: Connection::getClient();
    }

    /**
     * Creates the basic information for bulk indexing
     * @return void
     */
    public function init($index, $type, $pipeline = null)
    {
        $this->index = $index;
        $this->type = $type;
        $this->pipeline = $pipeline;

        $this->parameters =  [
            'index' => $this->getIndex(),
            'type' => $this->getType(),
            'body' => [],
        ];

        if ($this->getPipeline() != null) {
            $this->parameters['pipeline'] = $this->getPipeline();
        }
    }

    /**
     * Adds a new row to the indexer parameters
     *
     * @param int $uid The uid of the current record
     * @param array $body
     *
     * @return void
     */
    public function addRow($uid, $body)
    {
        //Build meta row for new row
        $this->parameters['body'][] = [
            'index' => [
                '_index' => $this->index,
                '_type' => $this->type,
                '_id' => $uid,
            ],

        ];

        $this->parameters['body'][] = $body;
    }

    public function addRows($uidField, $records)
    {
        foreach ($records as $record) {
            $this->addRow($record[$uidField], $record);
        }
    }

    /**
     * Executes a bulk insertion query
     *
     * @return array
     */
    public function execute()
    {
        $response = [];

        if (!empty($this->parameters['body'])) {

            /**
             * @var array
             */
            $response = $this->client->bulk($this->getParameters());

            if ($response['errors']) {
                $this->logger->error("Bulk Query response contains errors: ", $response);
            }
        }

        return $response;
    }

    /**
     * Deletes an existing document
     * @todo move this away from the bulkquery (does not fit its domain)
     *
     * @param  int $id
     * @return void
     */
    public function delete($id)
    {
        $params = [
            'index' => $this->index,
            'type' => $this->type,
            'id' => $id,
        ];

        if ($this->client->exists($params)) {
            $response = $this->client->delete($params);

            if ($response['errors']) {
                $this->logger->error("Delete Query response contains errors: ", $response);
            }
        }
    }

    /**
     * Resets the body (for batch indexing)
     *
     * @return void
     */
    public function resetBody()
    {
        $this->parameters['body'] = [];
    }
}

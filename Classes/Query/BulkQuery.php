<?php
namespace PAGEmachine\Searchable\Query;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Helper class to build up the parameter array for bulk indexing
 */
class BulkQuery extends AbstractQuery
{
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
     * @param string $index
     * @param string $pipeline
     */
    public function __construct(protected $index, protected $pipeline = null)
    {
        parent::__construct();

        $this->init();
    }

    /**
     * Creates the basic information for bulk indexing
     * @return void
     */
    public function init()
    {
        $this->parameters =  [
            'index' => $this->getIndex(),
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
                '_type' => '_doc',
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
            'type' => '_doc',
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

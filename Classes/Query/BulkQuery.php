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
     * @var string $index
     */
    protected $index;

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
     * @param string $index
     * @param string $type
     */
    public function __construct($index, $type, $pipeline = null)
    {
        parent::__construct();

        $this->index = $index;
        $this->type = $type;
        $this->pipeline = $pipeline;

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
     * @return void
     */
    public function execute()
    {
        if (!empty($this->parameters['body'])) {

            /**
             * @var array
             */
            $response = $this->client->bulk($this->getParameters());

            if ($response['errors']) {
                $this->logger->error("Bulk Query response contains errors: ", $response);
            }
        }
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

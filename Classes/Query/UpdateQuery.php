<?php
namespace PAGEmachine\Searchable\Query;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Query class to store update information
 * This is NOT the query for updating records, use a BulkQuery instead!
 */
class UpdateQuery extends AbstractQuery
{
    /**
     * Update index
     *
     * @var string
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
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->index = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['updateIndex']['name'];

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
            'body' => [
            ],
        ];
    }

    /**
     * Adds a new update query string
     *
     * @return array
     */
    public function addUpdate($type, $property, $id)
    {
        // Use querystring hash as id to mark each update only once
        $docid = sha1($type . "." . $property . ":" . $id);

        $this->parameters['id'] = $docid;
        $this->parameters['type'] = $type;
        $this->parameters['body']['property'] = $property;
        $this->parameters['body']['uid'] = $id;

        try {
              $response = $this->client->index($this->getParameters());
              return $response;
        } catch (\Exception $e) {
            $this->logger->error("Could not track update. Reason: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Adds a new update query string
     *
     * @return array
     */
    public function getUpdates($index, $type)
    {
        $recordids = [];

        $this->init();

        $this->parameters['type'] = $type;
        $this->parameters['body'] = [
            'query' => [
                'match_all' => new \stdClass(),
            ],
        ];


        $result = $this->client->search($this->parameters);

        if (empty($result['hits']['hits'])) {
            return [];
        }

        $updateParams = [];

        foreach ($result['hits']['hits'] as $hit) {
            //If this is a simple toplevel uid check, we can add this id directly to the updated uid list
            if ($hit['_source']['property'] == 'uid') {
                $recordids[$hit['_source']['uid']] = $hit['_source']['uid'];
            } else {
                 $updateParams[] = [
                    "term" => [
                        $hit['_source']['property'] => $hit['_source']['uid'],
                    ],
                 ];
            }
        }

        if (!empty($updateParams)) {
            $this->parameters['index'] = $index;
            $this->parameters['body'] = [
                '_source' => false,
                'query' => [
                    'bool' => [
                        'should' => $updateParams,
                        'minimum_should_match' => 1,
                    ],
                ],
            ];

            $result = $this->client->search($this->parameters);

            if (!empty($result['hits']['hits'])) {
                foreach ($result['hits']['hits'] as $hit) {
                    $recordids[$hit['_id']] = $hit['_id'];
                }
            }
        }

        return $recordids;
    }
}

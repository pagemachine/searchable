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
        $this->init();

        $this->parameters['type'] = $type;
        $this->parameters['body'] = [
            'size' => 9999,
            'query' => [
                'match_all' => new \stdClass(),
            ],
        ];


        $result = $this->client->search($this->parameters);

        if (empty($result['hits']['hits'])) {
            return [];
        }

        $recordids = [];

        foreach ($result['hits']['hits'] as $hit) {
            // If this is a simple toplevel uid check, we can add this id directly to the updated uid list
            if ($hit['_source']['property'] == 'uid') {
                $recordids[$hit['_source']['uid']] = $hit['_source']['uid'];
            } else { // Otherwise look up UID by sub property
                $this->parameters['index'] = $index;
                $this->parameters['body'] = [
                    '_source' => false,
                    'size' => 9999,
                    'query' => [
                        'bool' => [
                            'should' => [
                                'term' => [
                                    $hit['_source']['property'] => $hit['_source']['uid'],
                                ],
                            ],
                            'minimum_should_match' => 1,
                        ],
                    ],
                ];

                $subResult = $this->client->search($this->parameters);

                if (!empty($subResult['hits']['hits'])) {
                    foreach ($subResult['hits']['hits'] as $hit) {
                        $recordids[$hit['_id']] = $hit['_id'];
                    }
                }
            }
        }

        return $recordids;
    }
}

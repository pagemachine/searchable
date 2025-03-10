<?php
namespace PAGEmachine\Searchable\Query;

use PAGEmachine\Searchable\Queue\UpdateQueue;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * This file is part of the Pagemachine Searchable project.
 */

/**
 * Query class to store update information
 * This is NOT the query for updating records, use a BulkQuery instead!
 */
class UpdateQuery extends AbstractQuery
{
    protected UpdateQueue $updateQueue;

    /**
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->updateQueue = GeneralUtility::makeInstance(UpdateQueue::class);

        $this->init();
    }

    /**
     * Creates the basic information for bulk indexing
     * @return void
     */
    public function init()
    {
        $this->parameters =  [
            'index' => [],
            'body' => [],
        ];
    }

    /**
     * Adds a new update query string
     *
     */
    public function addUpdate($type, $property, $id): void
    {
        $this->updateQueue->enqueue($type, $property, $id);
    }

    /**
     * Adds a new update query string
     *
     * @return array
     */
    public function getUpdates($index, $type)
    {
        $this->init();

        $recordids = [];

        $results = $this->updateQueue->pendingUpdates($type);

        $updateParams = [];

        foreach ($results as $update) {
            //If this is a simple toplevel uid check, we can add this id directly to the updated uid list
            if ($update['property'] == 'uid') {
                $recordids[$update['property_uid']] = $update['property_uid'];
            } else {
                $updateParams[] = [
                    "term" => [
                        $update['property'] => $update['property_uid'],
                    ],
                ];
            }
        }

        if (!empty($updateParams)) {
            $this->parameters['index'] = $index;
            $this->parameters['body'] = [
                '_source' => false,
                'size' => '9999',
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
                    $recordids[$hit['_id']] = (int) $hit['_id'];
                }
            }
        }

        return $recordids;
    }
}

<?php
namespace PAGEmachine\Searchable\Query;

use PAGEmachine\Searchable\Domain\Repository\UpdateRepository;
use PAGEmachine\Searchable\Service\ExtconfService;
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
    protected UpdateRepository $updateRepository;

    /**
     * @return void
     */
    public function __construct()
    {
        parent::__construct();

        $this->updateRepository = GeneralUtility::makeInstance(UpdateRepository::class);

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
        $this->updateRepository->insertUpdate($type, $property, $id);
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

        $results = $this->updateRepository->findByType($type);

        $updateParams = [];

        foreach ($results as $hit) {
            //If this is a simple toplevel uid check, we can add this id directly to the updated uid list
            if ($hit->getProperty() == 'uid') {
                $recordids[$hit->getPropertyUid()] = $hit->getPropertyUid();
            } else {
                $updateParams[] = [
                    "term" => [
                        $hit->getProperty() => $hit->getPropertyUid(),
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

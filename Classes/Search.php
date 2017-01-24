<?php
namespace PAGEmachine\Searchable;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * The main class for searching
 */
class Search implements SingletonInterface {

    /**
     * Elasticsearch client
     * @var Client
     */
    protected $client;

    /**
     * @param Client|null $client
     */
    public function __construct(Client $client = null) {

        $this->client = $client ?: ClientBuilder::create()->build();
    }

    /**
     * @return Search
     */
    public static function getInstance() {

        return GeneralUtility::makeInstance(Search::class);

    }

    /**
     * Search everything (all indices and types) for the term
     * @param  string $term
     * @return array
     */
    public function search($term) {

        $params = [
            'body' => [
                'query' => [
                    'match' => [
                        '_all' => $term
                    ]
                ]
            ]
        ];

        $result = $this->client->search($params);
        return $result;


    }





}

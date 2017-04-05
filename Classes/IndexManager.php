<?php
namespace PAGEmachine\Searchable;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use PAGEmachine\Searchable\Service\ExtconfService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Manages some index level functions such as clearing the index
 */
class IndexManager implements SingletonInterface {

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
     * @return IndexManager
     */
    public static function getInstance() {

        return GeneralUtility::makeInstance(IndexManager::class);

    }

    /**
     * Returns index stats (for backend modules)
     *
     * @return array
     */
    public function getStats() {

        $stats['health'] = $this->client->cluster()->health();

        $info = [];

        foreach (ExtconfService::getIndices() as $language => $index) {

            $info[$language] = [
                'name' => $index,
                'language' => $language,
            ];

            foreach (ExtconfService::getIndexers() as $type => $config) {

                $info[$language]['types'][$type] = [
                    'name' => $type,
                    'documents' => $this->client->count([
                        'index' => $index,
                        'type' => $type
                    ])['count']
                ];
            }
        }

        $stats['indices'] = $info;

        return $stats;
    }

    /**
     * Deletes and recreates an index
     * @param  string $index
     * @param  array $mapping
     * @return array
     */
    public function resetIndex($index, $mapping = []) {

        $deleteParams = [
            'index' => $index
        ];

        if ($this->client->indices()->exists($deleteParams)) {

            $response = $this->client->indices()->delete($deleteParams);
        }

        $params = [
            'index' => $index,
            'body' => [
                'settings' => [
                    'number_of_shards' => 2,
                    'number_of_replicas' => 0
                ]
            ]
        ];

        if (!empty($mapping)) {

            $params['body']['mappings'] = $mapping;
        }

        $response = $this->client->indices()->create($params);
    }

    /**
     * Resets the update index
     * 
     * @return void
     */
    public function resetUpdateIndex() {

        $this->resetIndex(
            ExtconfService::getInstance()->getUpdateIndex()
        );


    }





}

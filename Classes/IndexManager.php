<?php
namespace PAGEmachine\Searchable;

use Elasticsearch\Client;
use PAGEmachine\Searchable\Configuration\ConfigurationManager;
use PAGEmachine\Searchable\Connection;
use PAGEmachine\Searchable\Service\ConfigurationMergerService;
use PAGEmachine\Searchable\Service\ExtconfService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\DebugUtility;


/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Manages some index level functions such as clearing the index
 */
class IndexManager implements SingletonInterface
{
    /**
     * Elasticsearch client
     * @var Client
     */
    protected $client;

    /**
     * @param Client|null $client
     */
    public function __construct(Client $client = null)
    {
        $this->client = $client ?: Connection::getClient();
    }

    /**
     * @return IndexManager
     */
    public static function getInstance()
    {
        return GeneralUtility::makeInstance(IndexManager::class);
    }

    /**
     * Returns index stats (for backend modules)
     *
     * @return array
     */
    public function getStats()
    {
        $stats['health'] = $this->client->cluster()->health();

        $info = [];

        foreach (ExtconfService::getIndices() as $nameIndex => $index) {
            $language = ExtconfService::getIndexLanguage($index);
            
            $info[$nameIndex] = [
                'name' => $index,
                'nameIndex' => $nameIndex,
                'language' => $language,
            ];

            foreach (ExtconfService::getIndexers() as $name => $config) {
                if(in_array($name,ExtconfService::getIndexIndexer($index))){
                $info[$nameIndex]['types'][$name] = [
                    'name' => $name,
                    'documents' => $this->client->count([
                        'index' => $index,
                        'type' => $config['config']['type'],
                    ])['count'],
                ];
             }
            }
        }

        $stats['indices'] = $info;

        return $stats;
    }

    /**
     * Deletes and recreates an index
     * @param  string $index
     * @return void
     */
    public function resetIndex($index)
    {
        $deleteParams = [
            'index' => $index,
        ];

        if ($this->client->indices()->exists($deleteParams)) {
            $response = $this->client->indices()->delete($deleteParams);
        }

        $this->createIndex($index);
    }

    /**
     * Creates an index. Checks if it exists before creating
     * @param  string $index
     * @return array
     */
    public function createIndex($index)
    {
        if ($this->client->indices()->exists(['index' => $index])) {
            return [];
        }

        $params = [
            'index' => $index,
            'body' => [
                'settings' => ConfigurationMergerService::merge(ExtconfService::getDefaultIndexSettings(), ExtconfService::getIndexSettings($index)),
            ],
        ];  

        $mapping = ConfigurationManager::getInstance()->getMapping($index);

        $mappingIndexer = [];

        if($index != 'searchable_updates'){
        $indexer = ExtconfService::getIndexIndexer($index)[0];
        
        $mappingIndexer = $mapping[$indexer];
        
        DebugUtility::debug($mappingIndexer, 'mappingIndexer');
        DebugUtility::debug($index, 'Index');

        if ($mappingIndexer['properties']['searchable_meta'] == null){
            $mappingIndexer['properties']['searchable_meta'] = [];
        }


        if (!empty($mapping)) {
            $params['body']['mappings'] = $mappingIndexer;
        }}else{

        }
        DebugUtility::debug($params, 'mapping');

        return $this->client->indices()->create($params);
    }

    /**
     * Resets the update index
     *
     * @return void
     */
    public function resetUpdateIndex()
    {
        $this->resetIndex(
            ExtconfService::getInstance()->getUpdateIndex()
        );
    }
}

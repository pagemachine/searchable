<?php
namespace PAGEmachine\Searchable;

use Elasticsearch\Client;
use PAGEmachine\Searchable\Configuration\ConfigurationManager;
use PAGEmachine\Searchable\Connection;
use PAGEmachine\Searchable\Domain\Repository\UpdateRepository;
use PAGEmachine\Searchable\Service\ConfigurationMergerService;
use PAGEmachine\Searchable\Service\ExtconfService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * This file is part of the Pagemachine Searchable project.
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
     * @var UpdateRepository
     */
    protected UpdateRepository $updateRepository;

    /**
     * @param Client|null $client
     */
    public function __construct(Client $client = null, UpdateRepository $updateRepository = null)
    {
        $this->client = $client ?: Connection::getClient();
        $this->updateRepository = $updateRepository ?: GeneralUtility::makeInstance(UpdateRepository::class);
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

        foreach (ExtconfService::getIndices() as $index) {
            $language = ExtconfService::getLanguageOfIndex($index);
            $configKey = ExtconfService::getConfigOfIndex($index);

            if (empty($info[$configKey])) {
                $info[$configKey] = [
                    'name' => $configKey,
                    'nameIndex' => $index,
                    'language' => $language,
                ];
            };

            foreach (ExtconfService::getInstance()->getIndexers() as $name => $config) {
                if ($name == ExtconfService::getIndexerKeyOfIndex($index)) {
                    $info[$configKey]['types'][$name] = [
                        'name' => $name,
                        'documents' => $this->client->count([
                            'index' => $index,
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
                'settings' => ConfigurationMergerService::merge(ExtconfService::getDefaultIndexSettings(), ExtconfService::getSettingsOfIndex($index)),
            ],
        ];

        $mapping = ConfigurationManager::getInstance()->getMapping($index);

        if (!empty($mapping)) {
            $params['body']['mappings'] = $mapping;
        }

        return $this->client->indices()->create($params);
    }

    /**
     * Resets the update index
     *
     * @return void
     */
    public function resetUpdateIndex()
    {
        $this->updateRepository->deleteAll();
    }
}

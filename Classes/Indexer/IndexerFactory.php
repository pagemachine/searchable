<?php

namespace PAGEmachine\Searchable\Indexer;

use PAGEmachine\Searchable\Configuration\ConfigurationManager;
use PAGEmachine\Searchable\Service\ExtconfService;
use PAGEmachine\Searchable\UndefinedIndexException;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

class IndexerFactory implements SingletonInterface
{
    /**
     * @var ObjectManager $objectManager
     */
    protected $objectManager;

    /**
     * @param ObjectManager $objectManager
     */
    public function injectObjectManager(ObjectManager $objectManager): void
    {
        $this->objectManager = $objectManager;
    }

    /**
     * Builds an array of indexers
     * @param  string $index
     * @param  int $language
     * @return array
     */
    public function makeIndexers($index = '', $language = 0)
    {
        $indexers = [];

        $indexerConfiguration = ConfigurationManager::getInstance()->getIndexerConfiguration();

        $indexerName = ExtconfService::getIndexIndexer($index);

        $indexer = $indexerConfiguration[$indexerName];

        if (empty($indexerName)) {
            throw new UndefinedIndexException('Indexer '. $indexerName .' for Index ' . $index . ' is not defined!');
        }

        $indexers[] = $this->objectManager->get($indexer['className'], $index, $language, $indexer['config']);

        return $indexers;
    }

    public function makeIndexersForSetup($language = 0)
    {
        $indexers = [];

        if (!empty(ExtconfService::getLanguageIndicies($language))) {
            $indicies = ExtconfService::getLanguageIndicies($language);
        } else {
            return [];
        }

        $indexerConfiguration = ConfigurationManager::getInstance()->getIndexerConfiguration();

        foreach ($indicies as $index) {
            foreach ($indexerConfiguration as $indexer) {
                if ($indexer['config']['type'] == ExtconfService::getIndexersConfiguration(ExtconfService::getIndexIndexer($index))['type']) {
                    $indexers[] = $this->objectManager->get($indexer['className'], $index, $language, $indexer['config']);
                }
            }
        }

        return $indexers;
    }

    /**
     * Builds a single indexer
     * @param  string $index
     * @param  int $language
     * @param  string  $type     The type the index is for
     * @return IndexerInterface|null
     */
    public function makeIndexer($index = '', $language = 0, $type = '')
    {

        $indexerConfiguration = ConfigurationManager::getInstance()->getIndexerConfiguration();

        if ($indexerConfiguration[$type]) {
            return $this->objectManager->get($indexerConfiguration[$type]['className'], $index, $language, $indexerConfiguration[$type]['config']);
        } else {
            return null;
        }
    }
}

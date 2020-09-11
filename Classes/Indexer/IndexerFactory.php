<?php
namespace PAGEmachine\Searchable\Indexer;

use PAGEmachine\Searchable\Configuration\ConfigurationManager;
use PAGEmachine\Searchable\Service\ExtconfService;
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
     *
     * @param  int $language
     * @return array
     */
    public function makeIndexers($language = 0)
    {
        $indexers = [];

        if (ExtconfService::hasIndex($language)) {
            $index = ExtconfService::getIndex($language);
        } else {
            return [];
        }

        $indexerConfiguration = ConfigurationManager::getInstance()->getIndexerConfiguration();

        foreach ($indexerConfiguration as $indexer) {
            $indexers[] = $this->objectManager->get($indexer['className'], $index, $language, $indexer['config']);
        }

        return $indexers;
    }

    /**
     * Builds a single indexer
     * @param  int $language language to set up
     * @param  string  $type     The type the index is for
     * @return IndexerInterface|null
     */
    public function makeIndexer($language = 0, $type = '')
    {
        if (ExtconfService::hasIndex($language)) {
            $index = ExtconfService::getIndex($language);
        } else {
            return null;
        }

        $indexerConfiguration = ConfigurationManager::getInstance()->getIndexerConfiguration();

        if ($indexerConfiguration[$type]) {
            return $this->objectManager->get($indexerConfiguration[$type]['className'], $index, $language, $indexerConfiguration[$type]['config']);
        } else {
            return null;
        }
    }
}

<?php
namespace PAGEmachine\Searchable\Indexer;

use PAGEmachine\Searchable\Configuration\ConfigurationManager;
use PAGEmachine\Searchable\Service\ExtconfService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;


/*
 * This file is part of the PAGEmachine Searchable project.
 */

class IndexerFactory implements SingletonInterface {

    /**
     * ObjectManager
     *
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     * @inject
     */
    protected $objectManager;

    /**
     * Builds an array of indexers
     *
     * @param  integer $language
     * @return array
     */
    public function makeIndexers($language = 0) {

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



}

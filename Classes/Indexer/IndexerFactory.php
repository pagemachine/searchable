<?php

namespace PAGEmachine\Searchable\Indexer;

use PAGEmachine\Searchable\Configuration\ConfigurationManager;
use PAGEmachine\Searchable\Service\ExtconfService;
use PAGEmachine\Searchable\UndefinedIndexException;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

class IndexerFactory implements SingletonInterface
{

    /**
     * Builds an array of indexers
     * @param  string $index
     */
    public function makeIndexerForIndex($index = '')
    {
        $indexerConfiguration = ConfigurationManager::getInstance()->getIndexerConfiguration();

        $indexerName = ExtconfService::getIndexerKeyOfIndex($index);

        $indexer = $indexerConfiguration[$indexerName];

        if (empty($indexerName)) {
            throw new UndefinedIndexException('Indexer ' . $indexerName . ' for Index ' . $index . ' is not defined!');
        }

        return GeneralUtility::makeInstance($indexer['className'], $index, ExtconfService::getLanguageOfIndex($index), $indexer['config']);
    }

    public function makeIndexersForLangauge($language = 0)
    {
        $indexers = [];

        if (!empty(ExtconfService::getIndecesByLanguage($language))) {
            $indices = ExtconfService::getIndecesByLanguage($language);
        } else {
            return [];
        }

        $indexerConfiguration = ConfigurationManager::getInstance()->getIndexerConfiguration();

        foreach ($indices as $index) {
            foreach ($indexerConfiguration as $indexer) {
                if ($indexer['config']['type'] == ExtconfService::getTypeOfIndexer(ExtconfService::getIndexerKeyOfIndex($index))) {
                    $indexers[] = GeneralUtility::makeInstance($indexer['className'], $index, $language, $indexer['config']);
                }
            }
        }

        return $indexers;
    }
}

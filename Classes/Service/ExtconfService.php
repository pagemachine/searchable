<?php

namespace PAGEmachine\Searchable\Service;

use PAGEmachine\Searchable\UndefinedIndexException;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * This file is part of the Pagemachine Searchable project.
 */

/**
 * Helper class for all extconf related settings
 */
class ExtconfService implements SingletonInterface
{
    /**
     * @return ExtconfService
     */
    public static function getInstance()
    {
        return GeneralUtility::makeInstance(ExtconfService::class);
    }

    /**
     * Creates a new ES indice for every index/indexer combination in the config. This keeps the configuration simple even if it is not the real index structure in elasticsearch.
     * This is necessary because since elastic 7.0 one index can only have one document type.
     *
     * @return array
     */
    protected static function getElasticsearchIndices()
    {
        $indices = [];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['indices'] as $indexKey => $index) {
            if (!isset($index['typo3_language'])) {
                throw new \Exception('Please set the "typo3_language" for the index ' . $indexKey, 1719935622);
            }

            if (isset($index['indexer']) && !empty($index['indexer'])) {
                $indexer = $index['indexer'];
            } else {
                // If no indexer are set use all defined indexers
                $indexer = array_keys($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['indexers']);
            }
            foreach ($indexer as $key) {
                $indices[$indexKey . '_' . $key] = $index;
                $indices[$indexKey . '_' . $key]['indexer'] = $key;
                $indices[$indexKey . '_' . $key]['configIndex'] = $indexKey;
            }
        }

        return $indices;
    }

    /**
     * Returns all available indices
     *
     * @return array
     */
    public static function getIndices()
    {
        $indicesConfiguration = ExtconfService::getElasticsearchIndices();

        return array_keys($indicesConfiguration);
    }


    /**
     * Returns the language indices for a given language, if set. Otherwise throws an error so no invalid indices are created
     *
     * @param  int $language
     * @return array $indices
     */
    public static function getIndicesByLanguage(int $language = 0)
    {
        $indicesConfiguration = ExtconfService::getElasticsearchIndices();
        $indices = [];

        foreach ($indicesConfiguration as $nameIndex => $index) {
            if ($index['typo3_language'] == $language) {
                $indices[] = $nameIndex;
            }
        }

        if (empty($indices)) {
            throw new UndefinedIndexException('');
        }
        return $indices;
    }

    /**
     * Returns the indice key of the config for a given elasticsearch index (Should only be needed for the overview in the backend module)
     *
     * @return string
     */
    public static function getConfigOfIndex($nameIndex = '')
    {
        $index = ExtconfService::getElasticsearchIndices()[$nameIndex]['configIndex'];

        if (empty($index)) {
            throw new UndefinedIndexException('Index ' . $nameIndex . ' is not defined!');
        }
        return $index;
    }

    /**
     * Returns the index language for a given name, if set. Otherwise throws an error so no invalid indices are created
     *
     * @param  string $nameIndex
     * @return int $language
     */
    public static function getLanguageOfIndex($nameIndex = '')
    {
        $language = ExtconfService::getElasticsearchIndices()[$nameIndex]['typo3_language'];

        if (!isset($language)) {
            throw new UndefinedIndexException('Language for Index ' . $nameIndex . ' is not defined!');
        }
        return $language;
    }

    /**
     * Returns the indexer for a given index name, if set. Otherwise throws an error
     *
     * @param  string $nameIndex
     * @return string $indexerName
     */
    public static function getIndexerKeyOfIndex(string $nameIndex)
    {
        $indexerName = ExtconfService::getElasticsearchIndices()[$nameIndex]['indexer'];

        if (empty($indexerName)) {
            throw new UndefinedIndexException('Indexer for Index ' . $nameIndex . ' is not defined!');
        }
        return $indexerName;
    }

    /**
     * Returns the index settings for a given index
     *
     * @param  string $indexName
     * @return array $settings
     */
    public static function getSettingsOfIndex($indexName)
    {
        $indices = ExtconfService::getElasticsearchIndices();

        if (!empty($indices[$indexName]['settings'])) {
            return $indices[$indexName]['settings'];
        }

        return [];
    }

    /**
     * Returns the environment for a given index
     *
     * @param string $indexName
     * @return array $environment
     */
    public static function getEnvironmentOfIndex($indexName)
    {
        $indices = ExtconfService::getElasticsearchIndices();

        if (!empty($indices[$indexName]['environment'])) {
            return $indices[$indexName]['environment'];
        }

        return [];
    }

    /**
     * Returns the default index settings
     *
     * @return array
     */
    public static function getDefaultIndexSettings()
    {
        return $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['defaultIndexSettings'] ?: [];
    }

    /**
     * Returns true if an index for the given name exists, otherwise false
     *
     * @param  string $nameIndex
     * @return bool
     */
    public static function hasIndex($nameIndex = '')
    {
        if (!empty(ExtconfService::getElasticsearchIndices()[$nameIndex])) {
            return true;
        }

        return false;
    }

    /**
     * Returns all defined types
     *
     * @return array
     */
    public function getIndexers()
    {
        $indexer = [];

        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['indexers'] as $key => $value) {
            if (!empty($value['config']['type'])) {
                throw new \Exception('Please remove the "type" config key for ' . $key, 1669216900);
            }
            $indexer[$key] = $value;
            $indexer[$key]['config']['type'] = $key;
        }

        return $indexer;
    }

    /**
     * Returns the meta fieldname used for preview, link etc.
     *
     * @return string
     */
    public static function getMetaFieldname()
    {
        return $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['metaField'];
    }

    /**
     * Returns the update index
     * @return string
     */
    public function getUpdateIndex()
    {
        return $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['updateIndex']['name'];
    }

    /**
     * Returns the hosts configuration
     *
     * @return array
     */
    public function getHostsSettings()
    {
        $hosts = explode(",", (string) $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['extensionManagement']['connection']['hosts']);

        return $hosts;
    }

    /**
     * Returns the globally registered query configuration
     *
     * @return array
     */
    public function getQueryConfiguration()
    {
        return $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['query'];
    }

    /**
     * Returns the globally registered query configuration
     *
     * @return array
     */
    public function getDefaultMappingConfiguration()
    {
        return $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['defaultMapping'];
    }

    /**
     * Returns the frontend domain to use for link building
     *
     * @return string
     */
    public function getFrontendDomain()
    {
        return $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['extensionManagement']['indexing']['domain'];
    }

    /**
     * Returns pipelines configuration
     *
     * @return array
     */
    public function getPipelines()
    {
        return $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['pipelines'];
    }
}

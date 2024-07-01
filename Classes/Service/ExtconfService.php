<?php

namespace PAGEmachine\Searchable\Service;

use PAGEmachine\Searchable\UndefinedIndexException;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * This file is part of the PAGEmachine Searchable project.
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
     * Creates a new indice for every Indexer. This keeps the configuration simple even if it is not the real index structure in elasticsearch.
     * This is necessary because since elastic 7.0 one indice can only have one document type.
     *
     * @return array
     */
    protected static function getElasticsearchIndices()
    {
        $indices = [];
        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['indices'] as $indeceKey => $indece) {
            $indexer = $indece['indexer'];

            if (!isset($indexer) || empty($indexer)) {
                // If no indexer are set use all defined indexers
                $indexer = array_keys($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['indexers']);
            }
            foreach ($indexer as $key) {
                $indices[$indeceKey . '_' . $key] = $indece;
                $indices[$indeceKey . '_' . $key]['indexer'] = $key;
                $indices[$indeceKey . '_' . $key]['configIndex'] = $indeceKey;
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
     * Returns the indice key of the config for a given elasticsearch index (Should only be needed for the overview in the backend module)
     *
     * @return string
     */
    public static function getConfigIndex($nameIndex = '')
    {
        $index = ExtconfService::getElasticsearchIndices()[$nameIndex]['configIndex'];

        if (empty($index)) {
            throw new UndefinedIndexException('Index ' . $nameIndex . ' is not defined!');
        }
        return $index;
    }

    /**
     * Returns the index name for a given language, if set. Otherwise throws an error so no invalid indices are created
     *
     * @param  string $nameIndex
     * @return string $index
     */
    public static function getIndex($nameIndex = '')
    {
        $index = ExtconfService::getElasticsearchIndices()[$nameIndex]['name'];

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
    public static function getIndexLanguage($nameIndex = '')
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
    public static function getIndexIndexer(string $nameIndex)
    {
        $indexerName = ExtconfService::getElasticsearchIndices()[$nameIndex]['indexer'];

        if (empty($indexerName)) {
            throw new UndefinedIndexException('Indexer for Index ' . $nameIndex . ' is not defined!');
        }
        return $indexerName;
    }

    /**
     * Returns the index language for a given name, if set. Otherwise throws an error so no invalid indices are created
     *
     * @param  int $language
     * @return array $indices
     */
    public static function getLanguageIndices($language = 0)
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
     * Returns the index settings for a given index
     *
     * @param  string $indexName
     * @return array $settings
     */
    public static function getIndexSettings($indexName)
    {
        $indeces = ExtconfService::getElasticsearchIndices();

        $settings = $indeces[$indexName]['settings'];

        if (!empty($settings)) {
            return $settings;
        }

        foreach ($indeces as $index) {
            if (($index['name'] == $indexName) && isset($index['settings'])) {
                return $index['settings'];
            }
        }

        return [];
    }

    /**
     * Returns the environment for a given index
     *
     * @param string $indexName
     * @return array $environment
     */
    public static function getIndexEnvironment($indexName)
    {
        foreach (ExtconfService::getElasticsearchIndices() as $index) {
            if ($index['name'] == $indexName && isset($index['environment'])) {
                return $index['environment'];
            }
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
    public static function getIndexers()
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
     * Returns raw indexer configuration
     *
     * @return array
     */
    public function getIndexerConfiguration()
    {
        return ExtconfService::getIndexers();
    }

    /**
     * Returns indexers configuration
     *
     * @param string $indexerName
     * @return array $config
     */
    public static function getIndexersConfiguration($indexerName)
    {
        return ExtconfService::getIndexers()[$indexerName]['config'];
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
        $hosts = explode(",", $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['extensionManagement']['connection']['hosts']);

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

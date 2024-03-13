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
     * Returns all available indices
     *
     * @return array
     */
    public static function getIndices()
    {
        $indicesConfiguration = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['indices'];
        $indices = [];

        foreach ($indicesConfiguration as $language => $index) {
            $indices[$language] = $index['name'];
        }

        return $indices;
    }

    /**
     * Returns the index name for a given language, if set. Otherwise throws an error so no invalid indices are created
     *
     * @param  int $language
     * @return string $index
     */
    public static function getIndex($language = 0)
    {
        $index = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['indices'][$language]['name'];

        if (empty($index)) {
            throw new UndefinedIndexException('Index for language ' . $language . ' is not defined!');
        }
        return $index;
    }

    /**
     * Returns the index settings for a given index
     *
     * @param  string $indexName
     * @return array $settings
     */
    public static function getIndexSettings($indexName)
    {
        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['indices'] as $index) {
            if ($index['name'] == $indexName && isset($index['settings'])) {
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
        foreach ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['indices'] as $index) {
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
     * Returns true if an index for the given language exists, otherwise false
     *
     * @param  int $language
     * @return bool
     */
    public static function hasIndex($language = 0)
    {
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['indices'][$language])) {
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
        return $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['indexers'];
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
        return $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['indexers'];
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

<?php
namespace PAGEmachine\Searchable\Service;

use PAGEmachine\Searchable\UndefinedIndexException;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Helper class for all extconf related settings
 */
class ExtconfService {

    /**
     * Returns all available indices
     *
     * @return array
     */
    public static function getIndices() {
        return $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['indices'];
    }

    /**
     * Returns the index name for a given language, if set. Otherwise throws an error so no invalid indices are created
     * 
     * @param  integer $language
     * @return string $index
     */
    public static function getIndex($language = 0) {

        $index = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['indices'][$language];

        if (empty($index)) {

            throw new UndefinedIndexException('Index for language ' . $language . ' is not defined!');
        }
        return $index;
    }

    /**
     * Returns true if an index for the given language exists, otherwise false
     *
     * @param  integer $language
     * @return boolean
     */
    public static function hasIndex($language = 0) {

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
    public static function getTypes() {

        return $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['types'];
    }

    /**
     * Returns the meta fieldname used for preview, link etc.
     * 
     * @return array
     */
    public static function getMetaFieldname() {

        return $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['metaField'];


    }



}

<?php
namespace PAGEmachine\Searchable\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Helper class to create a valid TypoScriptFrontendController on demand
 */
class TsfeUtility
{
    /**
     * Initializes TSFE. This is necessary to have proper environment for typoLink.
     *
     * @return    void
     */
    public static function createTSFE()
    {
        if (!is_object($GLOBALS['TSFE'])) {
            $GLOBALS['TSFE'] = GeneralUtility::makeInstance(TypoScriptFrontendController::class, $GLOBALS['TYPO3_CONF_VARS'], 1, '');
            $GLOBALS['TSFE']->connectToDB();
            $GLOBALS['TSFE']->initFEuser();
            $GLOBALS['TSFE']->determineId();
            $GLOBALS['TSFE']->initTemplate();
            $GLOBALS['TSFE']->getConfigArray();
            // Set linkVars, absRefPrefix, etc
            \TYPO3\CMS\Frontend\Page\PageGenerator::pagegenInit();
        }
    }
}

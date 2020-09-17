<?php
namespace PAGEmachine\Searchable\Utility;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Utility\EidUtility;

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
        // @extensionScannerIgnoreLine
        if (class_exists(EidUtility::class)) {
            // @extensionScannerIgnoreLine
            EidUtility::initTCA(); // @phpstan-ignore-line
        }

        if (!is_object($GLOBALS['TSFE'])) {
            $GLOBALS['TSFE'] = GeneralUtility::makeInstance(TypoScriptFrontendController::class, $GLOBALS['TYPO3_CONF_VARS'], 1, 0);

            if (method_exists($GLOBALS['TSFE'], 'connectToDB')) { // TYPO3v9+
                // @extensionScannerIgnoreLine
                $GLOBALS['TSFE']->connectToDB();
            }

            $GLOBALS['TSFE']->initFEuser();
            $GLOBALS['TSFE']->determineId();
            $GLOBALS['TSFE']->initTemplate();
            $GLOBALS['TSFE']->getConfigArray();
            $GLOBALS['TSFE']->preparePageContentGeneration();
        }
    }
}

<?php
namespace PAGEmachine\Searchable\Utility;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
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
        if (version_compare(VersionNumberUtility::getCurrentTypo3Version(), '10', '<')) {
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
                $_SERVER['HTTP_HOST'] = 'localhost';
                GeneralUtility::flushInternalRuntimeCaches();
                $GLOBALS['TSFE']->preparePageContentGeneration();
            }

            return;
        }

        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        $site = array_values($siteFinder->getAllSites())[0] ?? null;

        if ($site === null) {
            throw new \RuntimeException('No site found for TSFE setup', 1610444900);
        }

        $requestFactory = GeneralUtility::makeInstance(ServerRequestFactory::class);
        $request = $requestFactory->createServerRequest('get', 'http://localhost')
            ->withAttribute('site', $site)
            ->withAttribute('language', $site->getDefaultLanguage())
            ->withAttribute('routing', new PageArguments($site->getRootPageId(), '0', []))
            ->withAttribute('frontend.user', GeneralUtility::makeInstance(FrontendUserAuthentication::class));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $frontendController = GeneralUtility::makeInstance(
            TypoScriptFrontendController::class,
            GeneralUtility::makeInstance(Context::class),
            $request->getAttribute('site'),
            $request->getAttribute('language'),
            $request->getAttribute('routing'),
            $request->getAttribute('frontend.user')
        );
        $frontendController->fetch_the_id($request);
        $frontendController->getConfigArray($request);

        $GLOBALS['TSFE'] = $frontendController;
    }
}

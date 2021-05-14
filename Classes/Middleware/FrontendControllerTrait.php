<?php
declare(strict_types = 1);

namespace PAGEmachine\Searchable\Middleware;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

trait FrontendControllerTrait
{
    private function bootFrontendController(ServerRequestInterface $request): void
    {
        if (version_compare(VersionNumberUtility::getCurrentTypo3Version(), '10', '<')) {
            $frontendController = GeneralUtility::makeInstance(
                TypoScriptFrontendController::class,
                null,
                1,
                0
            );
            $frontendController->initFEuser();
            $frontendController->fetch_the_id();

            $GLOBALS['TSFE'] = $frontendController;

            return;
        }

        $site = $request->getAttribute('site');

        if ($site instanceof NullSite) {
            $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
            $site = array_values($siteFinder->getAllSites())[0] ?? $site;
            $request = $request->withAttribute('site', $site);
            $request = $request->withAttribute('language', $site->getDefaultLanguage());
            $request = $request->withAttribute('routing', new PageArguments($site->getRootPageId(), '0', []));
        }

        $frontendController = GeneralUtility::makeInstance(
            TypoScriptFrontendController::class,
            GeneralUtility::makeInstance(Context::class),
            $request->getAttribute('site'),
            $request->getAttribute('language'),
            $request->getAttribute('routing'),
            $request->getAttribute('frontend.user')
        );
        $frontendController->fetch_the_id($request);

        $GLOBALS['TSFE'] = $frontendController;
    }
}

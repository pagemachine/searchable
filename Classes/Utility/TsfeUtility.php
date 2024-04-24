<?php
namespace PAGEmachine\Searchable\Utility;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspectFactory;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
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
    public static function createTSFE(string $siteIdentifier = null, int $languageId = null)
    {
        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        $site = $siteIdentifier ? $siteFinder->getSiteByIdentifier($siteIdentifier) : array_values($siteFinder->getAllSites())[0] ?? null;

        if ($site === null) {
            throw new \RuntimeException('No site found for TSFE setup', 1610444900);
        }

        $siteLanguage = $site->getDefaultLanguage();

        if ($languageId !== null) {
            try {
                $siteLanguage = $site->getLanguageById($languageId);
            } catch (\InvalidArgumentException $e) {
                $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(self::class);
                $logger->warning(sprintf('Falling back to default language of site "%s": %s', $site->getIdentifier(), $e->getMessage()));
            }
        }

        $requestFactory = GeneralUtility::makeInstance(ServerRequestFactory::class);
        $request = $requestFactory->createServerRequest('get', 'http://localhost')
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('site', $site)
            ->withAttribute('language', $siteLanguage)
            ->withAttribute('routing', new PageArguments($site->getRootPageId(), '0', []))
            ->withAttribute('frontend.user', GeneralUtility::makeInstance(FrontendUserAuthentication::class));
        $GLOBALS['TYPO3_REQUEST'] = $request;

        $context = GeneralUtility::makeInstance(Context::class);
        $context->setAspect('language', LanguageAspectFactory::createFromSiteLanguage($siteLanguage));

        $frontendController = GeneralUtility::makeInstance(
            TypoScriptFrontendController::class,
            GeneralUtility::makeInstance(Context::class),
            $request->getAttribute('site'),
            $request->getAttribute('language'),
            $request->getAttribute('routing'),
            $request->getAttribute('frontend.user')
        );
        $frontendController->determineId($request);

        if ((new Typo3Version())->getMajorVersion() < 12) {
            $frontendController->getConfigArray($request);
        }

        $GLOBALS['TSFE'] = $frontendController;
    }
}

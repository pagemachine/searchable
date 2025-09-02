<?php
namespace PAGEmachine\Searchable\Utility;

use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspectFactory;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScriptFactory;
use TYPO3\CMS\Core\TypoScript\IncludeTree\SysTemplateRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/*
 * This file is part of the Pagemachine Searchable project.
 */

/**
 * Helper class to create a valid TypoScriptFrontendController on demand
 */
class TsfeUtility
{
    public function __construct(
        #[Autowire(service: 'cache.typoscript')]
        private readonly ?PhpFrontend $typoScriptCache = null,
        private readonly ?SysTemplateRepository $sysTemplateRepository = null,
        private readonly ?FrontendTypoScriptFactory $frontendTypoScriptFactory = null,
    ) {
    }

    /**
     * Initializes TSFE. This is necessary to have proper environment for typoLink.
     */
    public function createTSFE(string $siteIdentifier = null, int $languageId = null)
    {
        $previous = [
            'TYPO3_REQUEST' => $GLOBALS['TYPO3_REQUEST'] ?? null,
            'TSFE' => $GLOBALS['TSFE'] ?? null,
        ];

        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        $site = $siteIdentifier ? $siteFinder->getSiteByIdentifier($siteIdentifier) : array_values($siteFinder->getAllSites())[0] ?? null;

        # TODO: Get the actual page ID from the page indexer
        $pageId = $site ? $site->getRootPageId() : 0;

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
            ->withQueryParams(['id' => $pageId])
            ->withAttribute('frontend.user', GeneralUtility::makeInstance(FrontendUserAuthentication::class));

        $GLOBALS['TYPO3_REQUEST'] = $request;

        $context = GeneralUtility::makeInstance(Context::class);
        $context->setAspect('language', LanguageAspectFactory::createFromSiteLanguage($siteLanguage));

        // TYPO3 13+
        if (version_compare(GeneralUtility::makeInstance(Typo3Version::class)->getVersion(), '13.0', '>=')) {
            $frontendTypoScript = $this->getFrontendTypoScript($request, $pageId);
            $request = $request->withAttribute('frontend.typoscript', $frontendTypoScript);
            $GLOBALS['TYPO3_REQUEST'] = $request;

            return $previous;
        }

        $frontendController = GeneralUtility::makeInstance(
            TypoScriptFrontendController::class,
            GeneralUtility::makeInstance(Context::class),
            $request->getAttribute('site'),
            $request->getAttribute('language'),
            $request->getAttribute('routing'),
            $request->getAttribute('frontend.user')
        );
        $frontendController->determineId($request);

        $GLOBALS['TYPO3_REQUEST'] = $frontendController->getFromCache($GLOBALS['TYPO3_REQUEST']);

        $GLOBALS['TSFE'] = $frontendController;

        return $previous;
    }

    /**
     * Restore previously saved global request/TSFE state.
     */
    public function restoreTSFE(array $previous): void
    {
        if (array_key_exists('TYPO3_REQUEST', $previous)) {
            if ($previous['TYPO3_REQUEST'] === null) {
                unset($GLOBALS['TYPO3_REQUEST']);
            } else {
                $GLOBALS['TYPO3_REQUEST'] = $previous['TYPO3_REQUEST'];
            }
        }

        if (array_key_exists('TSFE', $previous)) {
            if ($previous['TSFE'] === null) {
                unset($GLOBALS['TSFE']);
            } else {
                $GLOBALS['TSFE'] = $previous['TSFE'];
            }
        }
    }

    /**
     * Get the FrontendTypoScript for a given request. Based on BackendConfigurationManager::class->getTypoScriptSetup
     */
    public function getFrontendTypoScript(ServerRequestInterface $request, int $currentPageId): FrontendTypoScript
    {
        $site = $request->getAttribute('site');

        $rootLine = [];
        $sysTemplateRows = [];
        $sysTemplateFakeRow = [
            'uid' => 0,
            'pid' => 0,
            'title' => 'Fake sys_template row to force extension statics loading',
            'root' => 1,
            'clear' => 3,
            'include_static_file' => '',
            'basedOn' => '',
            'includeStaticAfterBasedOn' => 0,
            'static_file_mode' => false,
            'constants' => '',
            'config' => '',
            'deleted' => 0,
            'hidden' => 0,
            'starttime' => 0,
            'endtime' => 0,
            'sorting' => 0,
        ];

        if ($currentPageId > 0) {
            $rootLine = GeneralUtility::makeInstance(RootlineUtility::class, $currentPageId)->get();
            $sysTemplateRows = $this->sysTemplateRepository->getSysTemplateRowsByRootline($rootLine, $request);
            ksort($rootLine);
        }

        $expressionMatcherVariables = [
            'request' => $request,
            'pageId' => $currentPageId,
            'page' => !empty($rootLine) ? $rootLine[array_key_first($rootLine)] : [],
            'fullRootLine' => $rootLine,
            'site' => $site,
        ];

        $typoScript = $this->frontendTypoScriptFactory->createSettingsAndSetupConditions($site, $sysTemplateRows, $expressionMatcherVariables, $this->typoScriptCache);
        $typoScript = $this->frontendTypoScriptFactory->createSetupConfigOrFullSetup(true, $typoScript, $site, $sysTemplateRows, $expressionMatcherVariables, '0', $this->typoScriptCache, null);

        return $typoScript;
    }
}

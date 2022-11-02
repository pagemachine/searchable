<?php
declare(strict_types = 1);

namespace PAGEmachine\Searchable\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

final class UriBuilder implements MiddlewareInterface
{
    /**
     * Process an incoming server request.
     *
     * Processes an incoming server request in order to produce a response.
     * If unable to produce the response itself, it may delegate to the provided
     * request handler to do so.
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Simple static check for speed and to avoid side effects for unrelated requests
        if (strpos($request->getUri()->getPath(), '/-/searchable/urls') !== 0) {
            return $handler->handle($request);
        }

        $this->bootFrontendController($request);

        $configurations = $request->getParsedBody()['configurations'] ?? [];
        $uris = [];
        $contentObjectRenderer = GeneralUtility::makeInstance(ContentObjectRenderer::class);

        foreach ($configurations as $index => $configuration) {
            $uris[$index] = $contentObjectRenderer->typoLink_URL($configuration);
        }

        $response = new JsonResponse($uris);

        return $response;
    }

    private function bootFrontendController(ServerRequestInterface $request): void
    {
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
            new PageArguments($site->getRootPageId(), '0', []),
            $request->getAttribute('frontend.user')
        );
        $frontendController->determineId($request);

        $GLOBALS['TSFE'] = $frontendController;
    }
}

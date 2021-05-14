<?php
declare(strict_types = 1);

namespace PAGEmachine\Searchable\Middleware;

use PAGEmachine\Searchable\Configuration\ConfigurationManager;
use PAGEmachine\Searchable\Preview\FluidPreviewRenderer;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Fluid\View\StandaloneView;

final class FluidPreview implements MiddlewareInterface
{
    use FrontendControllerTrait;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Simple static check for speed and to avoid side effects for unrelated requests
        if (strpos($request->getUri()->getPath(), '/-/searchable/fluid-previews') !== 0) {
            return $handler->handle($request);
        }

        $record = $request->getParsedBody()['record'] ?? null;
        $config = $request->getParsedBody()['config'] ?? null;

        if (empty($record)) {
            throw new \InvalidArgumentException('Missing "record" body argument', 1620998543);
        }

        if (empty($config)) {
            throw new \InvalidArgumentException('Missing "config" body argument', 1620998531);
        }

        $view = GeneralUtility::makeInstance(StandaloneView::class);
        $view->setTemplate($config['templateName']);

        if (!empty($config['fields'])) {
            $fields = [];

            foreach ($config['fields'] as $field) {
                $fields[$field] = $record[$field] ?? null;
            }

            $view->assign('fields', $fields);
        }

        $configurationManager = GeneralUtility::makeInstance(ObjectManager::class)->get(ConfigurationManagerInterface::class);
        $configuration = $configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
            'Searchable'
        );
        $view->setTemplateRootPaths($configuration['view']['templateRootPaths']);
        $view->setLayoutRootPaths($configuration['view']['layoutRootPaths']);
        $view->setPartialRootPaths($configuration['view']['partialRootPaths']);

        $this->bootFrontendController($request);

        $response = new Response();
        $response->getBody()->write($view->render());

        return $response;
    }
}

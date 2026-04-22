<?php
namespace PAGEmachine\Searchable\Preview;

use PAGEmachine\Searchable\Preview\RequestAwarePreviewRendererInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Core\View\ViewInterface;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

/*
 * This file is part of the Pagemachine Searchable project.
 */

/**
 * Fluid Preview Renderer. Uses a Fluid Standalone View to render previews
 */
class FluidPreviewRenderer extends AbstractPreviewRenderer implements PreviewRendererInterface, RequestAwarePreviewRendererInterface
{
    /**
     * @var ConfigurationManager $configurationManager
     */
    protected $configurationManager;

    protected ViewInterface $view;

    protected ViewFactoryInterface $viewFactory;

    /**
     * @var array
     */
    protected $config = [
        'templateName' => 'Preview/Default',

    ];

    public function __construct(...$arguments)
    {
        parent::__construct(...$arguments);

        $this->configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
        $this->viewFactory = GeneralUtility::makeInstance(ViewFactoryInterface::class);
    }

    public function setRequest(ServerRequestInterface $request): void
    {
        $this->prepareView($request);
    }

    /**
     * Renders the preview
     *
     * @param  array $record
     * @return string
     */
    public function render($record)
    {
        if ($this->config['fields']) {
            $assignFields = [];

            foreach ($this->config['fields'] as $fieldname) {
                if (isset($record[$fieldname])) {
                    $assignFields[$fieldname] = $record[$fieldname];
                }
            }

            $this->view->assign("fields", $assignFields);
        }

        $preview = $this->view->render($this->config['templateName']);

        return $preview;
    }

    protected function prepareView(ServerRequestInterface $request)
    {
        $configuration = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
            'Searchable'
        );

        $this->view = $this->viewFactory->create(new ViewFactoryData(
            templateRootPaths: $configuration['view']['templateRootPaths'],
            layoutRootPaths: $configuration['view']['layoutRootPaths'],
            partialRootPaths: $configuration['view']['partialRootPaths'],
            request: $request,
        ));
    }
}

<?php
namespace PAGEmachine\Searchable\Preview;

use PAGEmachine\Searchable\Preview\RequestAwarePreviewRendererInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManager;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;

/*
 * This file is part of the Pagemachine Searchable project.
 */

/**
 * Fluid Preview Renderer. Uses a Fluid Standalone View to render previews
 */
class FluidPreviewRenderer extends AbstractPreviewRenderer implements PreviewRendererInterface, RequestAwarePreviewRendererInterface
{
    /**
     * @var StandaloneView $view
     */
    protected $view;

    /**
     * @var ConfigurationManager $configurationManager
     */
    protected $configurationManager;

    /**
     * @var array
     */
    protected $config = [
        'templateName' => 'Preview/Default',

    ];

    public function __construct(...$arguments)
    {
        parent::__construct(...$arguments);

        $this->view = GeneralUtility::makeInstance(StandaloneView::class);
        $this->configurationManager = GeneralUtility::makeInstance(ConfigurationManager::class);
    }
    public function setRequest(ServerRequestInterface $request): void
    {
        if (method_exists($this->view, 'setRequest')) {
            $this->view->setRequest($request);
        }

        if (method_exists($this->configurationManager, 'setRequest')) {
            // TODO: Check if using a generic backend request is problematic
            $backendRequest = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
            $this->configurationManager->setRequest($backendRequest);
        }

        $this->prepareView();
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

        $preview = $this->view->render();

        return $preview;
    }


    /**
     * Prepares the view
     * @return void
     */
    protected function prepareView()
    {
        $configuration = $this->configurationManager->getConfiguration(
            ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK,
            'Searchable'
        );

        $this->view->setTemplateRootPaths($configuration['view']['templateRootPaths']);
        $this->view->setLayoutRootPaths($configuration['view']['layoutRootPaths']);
        $this->view->setPartialRootPaths($configuration['view']['partialRootPaths']);

        $this->view->setTemplate($this->config['templateName']);
    }
}

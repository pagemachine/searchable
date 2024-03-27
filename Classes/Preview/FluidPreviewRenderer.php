<?php
namespace PAGEmachine\Searchable\Preview;

use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Fluid\View\StandaloneView;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Fluid Preview Renderer. Uses a Fluid Standalone View to render previews
 */
class FluidPreviewRenderer extends AbstractPreviewRenderer implements PreviewRendererInterface
{
    /**
     * @var StandaloneView $view
     */
    protected $view;

    /**
     * @param StandaloneView $view
     */
    public function injectView(StandaloneView $view): void
    {
        $this->view = $view;
    }

    /**
     * @var ConfigurationManagerInterface $configurationManager
     */
    protected $configurationManager;

    /**
     * @param ConfigurationManagerInterface $configurationManager
     */
    public function injectConfigurationManager(ConfigurationManagerInterface $configurationManager): void
    {
        $this->configurationManager = $configurationManager;
    }

    /**
     * @var array
     */
    protected $config = [
        'templateName' => 'Preview/Default',
    ];

    /**
     * Renders the preview
     *
     * @param  array $record
     * @return string
     */
    public function render($record)
    {
        $this->prepareView();

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

<?php
namespace PAGEmachine\Searchable\Preview;

use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Fluid Preview Renderer. Uses a Fluid Standalone View to render previews
 */
class FluidPreviewRenderer extends AbstractPreviewRenderer implements PreviewRendererInterface
{
    /**
     * @var \TYPO3\CMS\Fluid\View\StandaloneView
     * @inject
     */
    protected $view;

    /**
     * @var \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface
     * @inject
     */
    protected $configurationManager;

    /**
     * @var array
     */
    protected $config = [
        'templateName' => 'Preview/Default',

    ];

    /**
     * @return void
     */
    public function initializeObject()
    {
        $this->prepareView();
    }

    /**
     * Renders the preview
     *
     * @param  array $config
     * @return string
     */
    public function render($record)
    {
        if ($this->config['fields']) {
            $assignFields = [];

            foreach ($this->config['fields'] as $fieldname) {
                $assignFields[$fieldname] = $record[$fieldname];
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
        $configuration = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);

        $this->view->setTemplateRootPaths($configuration['view']['templateRootPaths']);
        $this->view->setLayoutRootPaths($configuration['view']['layoutRootPaths']);
        $this->view->setPartialRootPaths($configuration['view']['partialRootPaths']);

        $this->view->setTemplate($this->config['templateName']);
    }
}

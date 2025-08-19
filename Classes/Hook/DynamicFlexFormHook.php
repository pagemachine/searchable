<?php
namespace PAGEmachine\Searchable\Hook;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Configuration\Event\AfterFlexFormDataStructureParsedEvent;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;

/*
 * This file is part of the Pagemachine Searchable project.
 */

class DynamicFlexFormHook
{
    /**
     * @var ConfigurationManagerInterface
     */
    protected $configurationManager;

    /**
     * @var TypoScriptService
     */
    protected $typoScriptService;

    public function __construct(ConfigurationManagerInterface $configurationManager, TypoScriptService $typoScriptService)
    {
        $this->configurationManager = $configurationManager;
        $this->typoScriptService = $typoScriptService;
    }
    /**
     * Array of allowed flexform identifiers for transformation
     *
     * @var array
     */
    protected $allowedIdentifiers = [
        'searchable_searchbar,list',
        'searchable_livesearchbar,list',
        'searchable_results,list',
    ];

    public function modifyDataStructure(AfterFlexFormDataStructureParsedEvent $event): void
    {
        $dataStructure = $this->parseDataStructureByIdentifierPostProcess($event->getDataStructure(), $event->getIdentifier());
        $event->setDataStructure($dataStructure);
    }

    /**
     * Hook used to add items based on TypoScript configuration
     *
     * @param  array $dataStructure
     * @param  array $identifier
     * @return array $dataStructure
     */
    public function parseDataStructureByIdentifierPostProcess($dataStructure, $identifier)
    {
        if ($identifier['tableName'] == 'tt_content' && $identifier['fieldName'] == 'pi_flexform' && in_array($identifier['dataStructureKey'], $this->allowedIdentifiers)) {
            [$pluginKey, $listType] = explode(",", (string) $identifier['dataStructureKey']);
            $dataStructure['sheets']['features'] = $this->buildFlexSettingsFromTSSettings($pluginKey);
        }
        return $dataStructure;
    }

    /**
     * Builds FlexForm settings from TS (basically creates a field for each element in $settings['features'])
     *
     * @param string $plugin
     * @return array
     */
    protected function buildFlexSettingsFromTSSettings($plugin)
    {
        $configuration = $this->getPluginSettings($plugin);

        $sheet = [
            'ROOT' => [
                'sheetTitle' => 'LLL:EXT:searchable/Resources/Private/Language/locallang_flexforms.xlf:flexform.features',
                'el' => [],
            ],
        ];



        if (!empty($configuration['settings']['features'])) {
            foreach ($configuration['settings']['features'] as $feature => $value) {
                $sheet['ROOT']['el']['settings.features.' . $feature] = $this->buildSingleField($feature, $value);
            }
        }
        return $sheet;
    }

    /**
     * Builds a single FlexForm field
     *
     * @param  string $fieldname
     * @param  string $value The default value to apply
     * @return array
     */
    protected function buildSingleField($fieldname, $value)
    {
        $field = [
            'label' => 'LLL:EXT:searchable/Resources/Private/Language/locallang_flexforms.xlf:flexform.features.' . $fieldname,
            'config' => [
                'type' => 'check',
                'default' => $value,
            ],
        ];

        return $field;
    }

    /**
     * Fetches the plugin settings.
     * Basically copied from \TYPO3\CMS\Extbase\Configuration\BackendConfigurationManager->getPluginConfiguration(),
     * but pulling plugin settings instead of module settings
     *
     * @param  string $pluginName
     * @return array
     */
    protected function getPluginSettings($pluginName)
    {
        $extensionName = 'searchable';

        if (($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface) {
            $request = $GLOBALS['TYPO3_REQUEST'];
        } else {
            $request = (new ServerRequest())->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        }

        if (method_exists($this->configurationManager, 'setRequest')) {
            $this->configurationManager->setRequest($request);
        }

        try {
            $full = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FULL_TYPOSCRIPT);
        } catch (\Throwable) {
            $full = [];
        }

        if (!isset($full['plugin.'])) {
            return [];
        }

        $pluginRoot = $full['plugin.'];
        $pluginConfiguration = [];

        $extensionSignature = 'tx_' . strtolower($extensionName) . '.';
        if (array_key_exists($extensionSignature, $pluginRoot) && is_array($pluginRoot[$extensionSignature])) {
            $pluginConfiguration = $this->typoScriptService->convertTypoScriptArrayToPlainArray($pluginRoot[$extensionSignature]);
        }
        if (!empty($pluginName)) {
            $pluginSignature = 'tx_' . strtolower($pluginName) . '.';
            if (array_key_exists($pluginSignature, $pluginRoot) && is_array($pluginRoot[$pluginSignature])) {
                $overruleConfiguration = $this->typoScriptService->convertTypoScriptArrayToPlainArray($pluginRoot[$pluginSignature]);
                ArrayUtility::mergeRecursiveWithOverrule($pluginConfiguration, $overruleConfiguration);
            }
        }

        return $pluginConfiguration;
    }
}

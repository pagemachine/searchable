<?php
namespace PAGEmachine\Searchable\Hook;

use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\BackendConfigurationManager;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

class DynamicFlexFormHook
{
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
                'TCEforms' => [
                    'sheetTitle' => 'LLL:EXT:searchable/Resources/Private/Language/locallang_flexforms.xlf:flexform.features',
                ],
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
            'TCEforms' => [
                'label' => 'LLL:EXT:searchable/Resources/Private/Language/locallang_flexforms.xlf:flexform.features.' . $fieldname,
                'config' => [
                    'type' => 'check',
                    'default' => $value,
                ],
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
        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $backendConfigurationManager = $objectManager->get(BackendConfigurationManager::class);
        $typoScriptService = $objectManager->get(TypoScriptService::class);

        $setup = $backendConfigurationManager->getTypoScriptSetup();

        $pluginConfiguration = [];
        if (is_array($setup['plugin.']['tx_' . strtolower($extensionName) . '.'])) {
            $pluginConfiguration = $typoScriptService->convertTypoScriptArrayToPlainArray($setup['plugin.']['tx_' . strtolower($extensionName) . '.']);
        }
        if ($pluginName !== null) {
            $pluginSignature = strtolower('tx_' . $pluginName);
            if (is_array($setup['plugin.'][$pluginSignature . '.'])) {
                $overruleConfiguration = $typoScriptService->convertTypoScriptArrayToPlainArray($setup['plugin.'][$pluginSignature . '.']);
                ArrayUtility::mergeRecursiveWithOverrule($pluginConfiguration, $overruleConfiguration);
            }
        }
        return $pluginConfiguration;
    }
}

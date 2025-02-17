<?php
namespace PAGEmachine\Searchable\DataCollector\TCA;

use PAGEmachine\Searchable\Utility\BinaryConversionUtility;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * This file is part of the Pagemachine Searchable project.
 */

/**
 * Helper class to process plain values such as checkbox or radio fields (TCA related)
 */
class PlainValueProcessor implements SingletonInterface
{
    /**
     *
     * @return PlainValueProcessor
     */
    public static function getInstance()
    {
        return GeneralUtility::makeInstance(self::class);
    }


    /**
     * Resolves the bitmask and puts in labels for checkboxes
     *
     * @param  int $value
     * @param  array $fieldTca
     * @return string
     */
    public function processCheckboxField($value, $fieldTca)
    {
        $items = [];

        $itemCount = count($fieldTca['items']);
        $activeItemKeys = BinaryConversionUtility::convertCheckboxValue($value, $itemCount);

        foreach ($activeItemKeys as $key) {
            $label = $fieldTca['items'][$key]['label'] ?? $fieldTca['items'][$key][0];

            if (str_starts_with((string) $label, 'LLL:')) {
                $label = $this->getLanguageService()->sL($label);
            }

            $items[] = $label;
        }

        return implode(", ", $items);
    }

    /**
     * Resolves radio fields
     *
     * @param  int $value
     * @param  array $fieldTca
     * @return string
     */
    public function processRadioField($value, $fieldTca)
    {
        $label = "";

        if (is_array($fieldTca['items'])) {
            foreach ($fieldTca['items'] as $set) {
                $setLabel = $set['label'] ?? $set[0];
                $setValue = $set['value'] ?? $set[1];

                if ((string)$setValue === (string)$value) {
                    $label = $setLabel;
                    break;
                }
            }
        }

        if (str_starts_with((string) $label, 'LLL:')) {
            $label = $this->getLanguageService()->sL($label);
        }

        return $label;
    }

    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}

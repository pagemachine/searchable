<?php
namespace PAGEmachine\Searchable\DataCollector\Utility;

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

class FieldListUtility implements SingletonInterface
{
    public const MODE_WHITELIST = 'whitelist';
    public const MODE_BLACKLIST = 'blacklist';

    /**
     * @return FieldListUtility
     */
    public static function getInstance()
    {
        return GeneralUtility::makeInstance(FieldListUtility::class);
    }

    /**
     * Creates a whitelist considering TCA and indexer configuration
     *
     * Respects the two config modes: "blacklist" and "whitelist"
     *
     * @param  array $configFields The fieldlist coming from indexer configuration
     * @param  array $tca The TCA configuration to consider
     * @param  string $configMode
     * @return array $fields The resulting list of fields
     */
    public function createFieldList($configFields, $tca, $configMode = self::MODE_WHITELIST)
    {
        $whitelist = $this->getWhitelistSystemFields($tca);

        foreach ($tca['columns'] as $key => $column) {
            $type = $column['config']['type'] ?? null;

            if ($this->shouldInclude($key, $configFields, $configMode)) {
                $whitelist[] = $key;
            }
        }

        return $whitelist;
    }

    /**
     * Returns the whitelisted system fields (always enabled)
     *
     * @param array $tca
     * @return array
     */
    protected function getWhitelistSystemFields($tca)
    {
        $systemFields = [
            'uid',
            'pid',
        ];
        $languageField = $tca['ctrl']['languageField'] ?? null;

        if (!empty($languageField)) {
            $systemFields[] = $languageField;
        }

        return $systemFields;
    }

    /**
     * Returns true if this field should be excluded
     *
     * @param  string  $fieldname
     * @param array $fieldList
     * @param string $mode
     * @return bool
     */
    public function shouldInclude($fieldname, $fieldList, $mode)
    {
        $returnValue = $mode == self::MODE_WHITELIST ? true : false;

        if (in_array($fieldname, $fieldList)) {
            return $returnValue;
        }

        return !$returnValue;
    }
}

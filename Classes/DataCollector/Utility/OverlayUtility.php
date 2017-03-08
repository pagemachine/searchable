<?php
namespace PAGEmachine\Searchable\DataCollector\Utility;

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;


/*
 * This file is part of the PAGEmachine Searchable project.
 */

class OverlayUtility implements SingletonInterface {

	/**
	 * @return OverlayUtility
	 */
	public static function getInstance() {

		return GeneralUtility::makeInstance(OverlayUtility::class);
	}

   /**
     * Determine if a field needs an overlay
     * Copied from PageRepository
     *
     * @todo remove this in V8 (overlay mechanism no longer exists then, the translated record holds the correct merged values from the start)
     *
     * @param string $table TCA tablename
     * @param string $field TCA fieldname
     * @param mixed $value Current value of the field
     * @return bool Returns TRUE if a given record field needs to be overlaid
     */
    public function shouldFieldBeOverlaid($table, $field, $value)
    {
        $l10n_mode = isset($GLOBALS['TCA'][$table]['columns'][$field]['l10n_mode'])
            ? $GLOBALS['TCA'][$table]['columns'][$field]['l10n_mode']
            : '';

        $shouldFieldBeOverlaid = true;

        if ($l10n_mode === 'exclude') {
            $shouldFieldBeOverlaid = false;
        } elseif ($l10n_mode === 'mergeIfNotBlank') {
            $checkValue = $value;

            // 0 values are considered blank when coming from a group field
            if (empty($value) && $GLOBALS['TCA'][$table]['columns'][$field]['config']['type'] === 'group') {
                $checkValue = '';
            }

            if ($checkValue === [] || !is_array($checkValue) && trim($checkValue) === '') {
                $shouldFieldBeOverlaid = false;
            }
        }

        return $shouldFieldBeOverlaid;
    }

}

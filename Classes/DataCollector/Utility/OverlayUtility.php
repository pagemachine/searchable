<?php
namespace PAGEmachine\Searchable\DataCollector\Utility;

use PAGEmachine\Searchable\DataCollector\TCA\FormDataRecord;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;


/*
 * This file is part of the PAGEmachine Searchable project.
 */

class OverlayUtility implements SingletonInterface {

    /**
     * @var PageRepository
     */
    protected $pageRepository;

	/**
	 * @return OverlayUtility
	 */
	public static function getInstance() {

		return GeneralUtility::makeInstance(OverlayUtility::class);
	}

    /**
     *
     * @param PageRepository|null $pageRepository
     */
    public function __construct(PageRepository $pageRepository = null) {

        $this->pageRepository = $pageRepository ?: GeneralUtility::makeInstance(PageRepository::class);
    }

    /**
     * Basically imitates PageRepository->getRecordOverlay, just for the FormDataInput
     *
     * @param  string         $table
     * @param  array          $record
     * @param  int            $language
     * @param  array          $fieldWhitelist
     * @return array
     */
    public function languageOverlay($table, $record, $language, $fieldWhitelist = []) {

        $tca = $GLOBALS['TCA'][$table];
        
        $rawOverlay = $this->pageRepository->getRecordOverlay($table, [
            'uid' => $record['uid'],
            'pid' => $record['pid'],
            $tca['ctrl']['languageField'] => $record[$tca['ctrl']['languageField']][0]
            ], $language);


        // PageRepository says this is not a valid record in this language, so don't return it
        // Examples: R(1), language 0 | R(0), language 1, olMode 'hideNonTranslated' | R(1), language 0 (invalid combination)
        if ($rawOverlay == null) {

            return [];
        }

        //If there is no difference between source and raw OL id, no overlay is needed. Return record as-is
        if (!isset($rawOverlay['_LOCALIZED_UID']) || $rawOverlay['_LOCALIZED_UID'] == $record['uid']) {

            return $record;
        }

        //If we are here, we have a raw overlay which is in the correct language, and the record which must be in default language
        $translationData = FormDataRecord::getInstance()->getRecord($rawOverlay['_LOCALIZED_UID'], $table, $fieldWhitelist);
        $translationRecord = $translationData['databaseRow'];

        foreach ($record as $key => $field) {

            if ($key == "uid" || $key == "pid") {

                continue;
            }

            //If the FE overlay differs from the raw base record, replace the field with the translated field in the processed record
            if ($this->shouldFieldBeOverlaid($table, $key, $translationData['defaultLanguageRow'][$key])) {

                $record[$key] = $translationRecord[$key];
            }
        }

        return $record;
    }

    /**
     * Simplified workflow for pages
     *
     * @param  array          $record
     * @param  int            $language
     * @return array
     */
    public function pagesLanguageOverlay($record, $language) {

        $rawOverlay = $this->pageRepository->getPageOverlay([
            'uid' => $record['uid'],
            'pid' => $record['pid']
        ], $language);

        // PageRepository says this is not a valid record in this language, so don't return it
        if ($rawOverlay == null) {

            return [];
        }

        return $rawOverlay;
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

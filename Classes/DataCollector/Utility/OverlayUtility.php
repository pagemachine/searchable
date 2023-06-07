<?php
namespace PAGEmachine\Searchable\DataCollector\Utility;

use PAGEmachine\Searchable\DataCollector\TCA\FormDataRecord;
use PAGEmachine\Searchable\Utility\TsfeUtility;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

class OverlayUtility implements SingletonInterface
{
    /**
     * @var PageRepository
     */
    protected $pageRepository;

    /**
     * @return OverlayUtility
     */
    public static function getInstance()
    {
        return GeneralUtility::makeInstance(OverlayUtility::class);
    }

    /**
     *
     * @param PageRepository|null $pageRepository
     */
    public function __construct(PageRepository $pageRepository = null)
    {
        TsfeUtility::createTSFE();

        $this->pageRepository = $pageRepository ?: GeneralUtility::makeInstance(PageRepository::class);
    }

    /**
     * Basically imitates PageRepository->getRecordOverlay, just for the FormDataInput
     *
     * @param  string         $table
     * @param  array          $record
     * @param  int            $language
     * @param  array          $fieldWhitelist
     * @param  mixed          $overlayMode
     * @return array
     */
    public function languageOverlay($table, $record, $language, $fieldWhitelist = [], $overlayMode = 1)
    {
        $tca = $GLOBALS['TCA'][$table];

        if (isset($tca['ctrl']['languageField'])) {
            $rawOverlay = $this->pageRepository->getRecordOverlay($table, [
                'uid' => $record['uid'],
                'pid' => $record['pid'],
                $tca['ctrl']['languageField'] => $record[$tca['ctrl']['languageField']],
            ], $language, $overlayMode);
        } elseif ($language === 0) {
            return $record;
        }

        // PageRepository says this is not a valid record in this language, so don't return it
        // Examples: R(1), language 0 | R(0), language 1, olMode 'hideNonTranslated' | R(1), language 0 (invalid combination)
        if (empty($rawOverlay)) {
            return [];
        }

        if ((int)$overlayMode === 0 && $rawOverlay[$tca['ctrl']['languageField']] != $language) {
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

            $record[$key] = $translationRecord[$key];
        }

        return $record;
    }

    /**
     * Simplified workflow for pages
     *
     * @param  array          $record
     * @param  int            $language
     * @param  int            $overlayMode
     * @return array
     */
    public function pagesLanguageOverlay($record, $language, $overlayMode = 1)
    {
        $rawOverlay = $this->pageRepository->getPageOverlay($record, $language);

        // Simulate disabled overlay mode for pages
        if ((int)$overlayMode === 0 && $language > 0 && empty($rawOverlay['_PAGES_OVERLAY'])) {
            return [];
        }

        return $rawOverlay;
    }
}

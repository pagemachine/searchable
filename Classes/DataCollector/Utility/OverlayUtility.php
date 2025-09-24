<?php
namespace PAGEmachine\Searchable\DataCollector\Utility;

use PAGEmachine\Searchable\DataCollector\TCA\FormDataRecord;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Exception\Page\PageNotFoundException;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Type\Bitmask\PageTranslationVisibility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/*
 * This file is part of the Pagemachine Searchable project.
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
    public function languageOverlay($table, $record, $language, $fieldWhitelist = [], mixed $overlayMode = 1)
    {
        $tca = $GLOBALS['TCA'][$table];

        if (isset($tca['ctrl']['languageField'])) {
            $tempRecord = [
                'uid' => $record['uid'],
                'pid' => $record['pid'],
                $tca['ctrl']['languageField'] => $record[$tca['ctrl']['languageField']],
            ];

            $context = GeneralUtility::makeInstance(Context::class);
            $rawOverlay = $this->pageRepository->getLanguageOverlay($table, $tempRecord, $context->getAspect('language'));
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
     * Overlay Workflow for pages. Based on PageInformationFactory::class->settingLanguage
     *
     * @param  array $record
     * @throws PageNotFoundException
     * @return array
     */
    public function pagesLanguageOverlay($record)
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $languageAspect = $context->getAspect('language');

        $languageId = $languageAspect->getId();
        $languageContentId = $languageAspect->getContentId();

        $pageRecord = $record;
        $pageRecordOverlayed = $this->pageRepository->getPageOverlay($pageRecord, $languageAspect);

        $pageTranslationVisibility = new PageTranslationVisibility((int)($pageRecord['l18n_cfg'] ?? 0));
        if ($languageAspect->getId() > 0) {
            // If the incoming language is set to another language than default
            $olRec = $pageRecordOverlayed;
            $overlaidLanguageId = (int)($olRec['sys_language_uid'] ?? 0);
            if ($overlaidLanguageId !== $languageAspect->getId()) {
                // If requested translation is not available
                if ($pageTranslationVisibility->shouldHideTranslationIfNoTranslatedRecordExists()) {
                    throw new PageNotFoundException('Page is not available in the requested language.', 1754384426);
                }
                switch ($languageAspect->getLegacyLanguageMode()) {
                    case 'strict':
                        throw new PageNotFoundException('Page is not available in the requested language (strict).', 1754384467);

                    case 'content_fallback':
                        // Setting content uid (but leaving the sys_language_uid) when a content_fallback value was found.
                        foreach ($languageAspect->getFallbackChain() as $orderValue) {
                            if ($orderValue === '0' || $orderValue === 0 || $orderValue === '') {
                                $languageContentId = 0;
                                break;
                            }
                            if (MathUtility::canBeInterpretedAsInteger($orderValue) && $overlaidLanguageId === (int)$orderValue) {
                                $languageContentId = (int)$orderValue;
                                break;
                            }
                            if ($orderValue === 'pageNotFound') {
                                // The existing fallbacks have not been found, but instead of continuing page rendering
                                // with default language, a "page not found" message should be shown instead.
                                throw new PageNotFoundException('Page is not available in the requested language (fallbacks did not apply).', 1754384524);
                            }
                        }
                        break;
                    default:
                        // Default is that everything defaults to the default language.
                        $languageId = ($languageContentId = 0);
                }
            }

            // Define the language aspect again now
            $languageAspect = new LanguageAspect(
                $languageId,
                $languageContentId,
                $languageAspect->getOverlayType(),
                $languageAspect->getFallbackChain()
            );
        }

        if ((!$languageAspect->getContentId() || !$languageAspect->getId())
            && $pageTranslationVisibility->shouldBeHiddenInDefaultLanguage()
        ) {
            // If default language is not available
            throw new PageNotFoundException('Page is not available in default language.', 1754384591);
        }

        return $pageRecordOverlayed;
    }
}

<?php
namespace PAGEmachine\Searchable\ViewHelpers;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * SiteLanguageViewHelper
 * Simple ViewHelper to return the current site language
 */
class SiteLanguageViewHelper extends AbstractViewHelper
{
    /**
     * @return int
     */
    public function render()
    {
        if (is_object($this->getTypoScriptFrontendController())) {
            if (version_compare(VersionNumberUtility::getCurrentTypo3Version(), '9', '<')) {
                // @phpstan-ignore-next-line
                return $this->getTypoScriptFrontendController()->sys_language_uid;
            } else {
                return $this->getLanguageAspect()->getId();
            }
        }

        return 0;
    }

    /**
     * @return \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController|null
     * @codeCoverageIgnore
     */
    public function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }

    /**
     * @return \TYPO3\CMS\Core\Context\LanguageAspect
     * @codeCoverageIgnore
     */
    public function getLanguageAspect()
    {
        return GeneralUtility::makeInstance(Context::class)->getAspect('language');
    }
}

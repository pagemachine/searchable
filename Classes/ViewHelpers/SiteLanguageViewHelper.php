<?php
namespace PAGEmachine\Searchable\ViewHelpers;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

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
            return $this->getTypoScriptFrontendController()->sys_language_uid;
        }
        return 0;
    }

    /**
     * @return TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController
     * @codeCoverageIgnore
     */
    public function getTypoScriptFrontendController()
    {
        return $GLOBALS['TSFE'];
    }
}

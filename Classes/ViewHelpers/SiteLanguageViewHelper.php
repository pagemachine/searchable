<?php
namespace PAGEmachine\Searchable\ViewHelpers;

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

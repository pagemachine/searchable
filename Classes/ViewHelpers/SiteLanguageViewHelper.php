<?php
namespace PAGEmachine\Searchable\ViewHelpers;

use PAGEmachine\Searchable\LanguageIdTrait;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractViewHelper;

/*
 * This file is part of the Pagemachine Searchable project.
 */

/**
 * SiteLanguageViewHelper
 * Simple ViewHelper to return the current site language
 */
class SiteLanguageViewHelper extends AbstractViewHelper
{
    use LanguageIdTrait;

    /**
     * @return int
     */
    public function render()
    {
        return $this->getLanguageId();
    }
}

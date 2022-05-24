<?php
declare(strict_types = 1);

namespace PAGEmachine\Searchable;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;

trait LanguageIdTrait
{
    protected function getLanguageId(): int
    {
        return (int)GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('language', 'id');
    }
}

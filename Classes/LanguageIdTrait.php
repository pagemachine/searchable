<?php
declare(strict_types = 1);

namespace PAGEmachine\Searchable;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;

trait LanguageIdTrait
{
    protected function getLanguageId(): int
    {
        if (class_exists(Context::class)) {
            return (int)GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('language', 'id');
        }

        // @extensionScannerIgnoreLine
        return $GLOBALS['TSFE']->sys_language_uid ?? 0; // @phpstan-ignore-line
    }
}

<?php
if (!defined('TYPO3')) {
    die('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerPageTSConfigFile(
    'searchable',
    'FILE:EXT:searchable/Configuration/PageTS/ContentElementWizard.ts',
    'Searchable TSConfig'
);

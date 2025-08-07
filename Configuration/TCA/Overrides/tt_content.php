<?php
if (!defined('TYPO3')) {
    die('Access denied.');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'Searchable',
    'Searchbar',
    'LLL:EXT:searchable/Resources/Private/Language/locallang_be.xlf:searchbar_title',
    'actions-search',
    'Search',
    'LLL:EXT:searchable/Resources/Private/Language/locallang_be.xlf:searchbar_description'
);
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['searchable_searchbar'] = 'select_key, pages, recursive';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['searchable_searchbar'] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    'searchable_searchbar',
    'FILE:EXT:searchable/Configuration/Flexforms/Searchbar.xml'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'Searchable',
    'LiveSearchbar',
    'LLL:EXT:searchable/Resources/Private/Language/locallang_be.xlf:live_searchbar_title',
    'actions-search',
    'Search',
    'LLL:EXT:searchable/Resources/Private/Language/locallang_be.xlf:live_searchbar_description'
);
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['searchable_livesearchbar'] = 'select_key, pages, recursive';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['searchable_livesearchbar'] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    'searchable_livesearchbar',
    'FILE:EXT:searchable/Configuration/Flexforms/LiveSearchbar.xml'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'Searchable',
    'Results',
    'LLL:EXT:searchable/Resources/Private/Language/locallang_be.xlf:results_title',
    'actions-search',
    'Search',
    'LLL:EXT:searchable/Resources/Private/Language/locallang_be.xlf:results_description'
);
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['searchable_results'] = 'select_key, pages, recursive';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['searchable_results'] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    'searchable_results',
    'FILE:EXT:searchable/Configuration/Flexforms/Results.xml'
);

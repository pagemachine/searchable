<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'Searchable',
    'Searchbar',
    'Searchable: Search bar'
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
    'Searchable: Live Search bar (AJAX)'
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
    'Searchable: Results'
);
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_excludelist']['searchable_results'] = 'select_key, pages, recursive';
$GLOBALS['TCA']['tt_content']['types']['list']['subtypes_addlist']['searchable_results'] = 'pi_flexform';
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    'searchable_results',
    'FILE:EXT:searchable/Configuration/Flexforms/Results.xml'
);

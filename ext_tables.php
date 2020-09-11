<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
    'searchable',
    'Configuration/TypoScript',
    'Searchable'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'PAGEmachine.Searchable',
    'Searchbar',
    'Searchable: Search bar'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'PAGEmachine.Searchable',
    'LiveSearchbar',
    'Searchable: Live Search bar (AJAX)'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    'searchable_searchbar',
    'FILE:EXT:searchable/Configuration/Flexforms/Searchbar.xml'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    'searchable_livesearchbar',
    'FILE:EXT:searchable/Configuration/Flexforms/LiveSearchbar.xml'
);
\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPiFlexFormValue(
    'searchable_results',
    'FILE:EXT:searchable/Configuration/Flexforms/Results.xml'
);

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::registerPageTSConfigFile(
    'searchable',
    'FILE:EXT:searchable/Configuration/PageTS/ContentElementWizard.ts',
    'Searchable TSConfig'
);

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerPlugin(
    'PAGEmachine.Searchable',
    'Results',
    'Searchable: Results'
);

// Backend module
if (TYPO3_MODE === 'BE') {
    \TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
        'PAGEmachine.Searchable',
        'web',
        'searchable',
        '',
        array(
            'Backend' => 'start, search, request, resetIndices, indexFull, indexPartial'
        ),
        array(
            'access'    => 'user,group',
            'icon'      => 'EXT:searchable/ext_icon.svg',
            'labels'    => 'LLL:EXT:searchable/Resources/Private/Language/locallang_mod.xlf'
        )
    );
}

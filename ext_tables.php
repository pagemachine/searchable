<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

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

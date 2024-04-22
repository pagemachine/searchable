<?php

use PAGEmachine\Searchable\Controller\BackendController;
use TYPO3\CMS\Extbase\Utility\ExtensionUtility;

if (!defined('TYPO3')) {
    die('Access denied.');
}

ExtensionUtility::registerModule(
    'Searchable',
    'web',
    'searchable',
    '',
    [
        BackendController::class => 'start, search, request, resetIndices, indexFull, indexPartial',
    ],
    [
        'access' => 'user,group',
        'icon' => 'EXT:searchable/Resources/Public/Icons/Extension.svg',
        'labels' => 'LLL:EXT:searchable/Resources/Private/Language/locallang_mod.xlf',
    ]
);

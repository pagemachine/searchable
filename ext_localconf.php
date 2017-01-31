<?php

use PAGEmachine\Searchable\Command\IndexCommandController;

if (TYPO3_MODE === 'BE') {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers']['searchable'] = IndexCommandController::class;
}

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable'] = [
    'indices' => [
        0 => 'typo3'
    ],
    'indexers' => [
        0 => \PAGEmachine\Searchable\Indexer\PagesIndexer::class

    ]
];

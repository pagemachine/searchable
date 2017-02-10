<?php

use PAGEmachine\Searchable\Command\IndexCommandController;

if (TYPO3_MODE === 'BE') {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers']['searchable'] = IndexCommandController::class;
}

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable'] = [
    'indices' => [
        0 => 'typo3'
    ],
    'types' => [
        'pages' => [
            'indexer' => \PAGEmachine\Searchable\Indexer\PagesIndexer::class
        ],
        'news' => [
            'indexer' => \PAGEmachine\Searchable\Indexer\TcaBasedIndexer::class,
            'config' => [
                'type' => 'news',
                'table' => 'tx_news_domain_model_news',
                'fields' => ['uid', 'title', 'teaser', 'bodytext', 'author', 'author_email', 'tags'],
                'subtypes' => [
                    'tags' => [
                        'indexer' => \PAGEmachine\Searchable\Indexer\TcaBasedIndexer::class,
                        'config' => [
                            'fields' => [
                                'title'
                            ]
                        ]
                    ]
                ]
            ]
        ]
    ],
];

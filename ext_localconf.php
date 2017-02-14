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
        // 'pages' => [
        //     'indexer' => \PAGEmachine\Searchable\Indexer\PagesIndexer::class
        // ],
        // 'news' => [
        //     'indexer' => \PAGEmachine\Searchable\Indexer\TcaBasedIndexer::class,
        //     'config' => [
        //         'type' => 'news',
        //         'table' => 'tx_news_domain_model_news',
        //         'excludeFields' => [
        //             'path_segment',
        //             'import_id',
        //             'import_source'
        //         ],
        //         'subtypes' => [
        //             'tags' => [
        //                 'indexer' => \PAGEmachine\Searchable\Indexer\TcaBasedIndexer::class,
        //                 'config' => [
        //                 ]
        //             ],
        //             'categories' => [
        //                 'indexer' => \PAGEmachine\Searchable\Indexer\TcaBasedIndexer::class,
        //                 'config' => [
        //                 ]
        //             ]
        //         ]
        //     ]
        // ]
        'styleguide' => [
            'indexer' => \PAGEmachine\Searchable\Indexer\TcaBasedIndexer::class,
            'config' => [
                'type' => 'styleguide',
                'table' => 'tx_styleguide_forms',
                'subtypes' => [
                    'select_25' => [
                        'indexer' => \PAGEmachine\Searchable\Indexer\TcaBasedIndexer::class,
                        'config' => [
                            'excludeFields' => [
                                'perms_userid',
                                'perms_groupid',
                                'perms_user',
                                'perms_group',
                                'perms_everybody'
                            ]
                        ],
                    ]
                ]

            ]

        ]
    ],
];

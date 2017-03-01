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
        // 'news' => [
        //     'indexer' => \PAGEmachine\Searchable\Indexer\TcaBasedIndexer::class,
        //     'type' => 'news',
        //     'config' => [
        //         'table' => 'tx_news_domain_model_news',
        //         'excludeFields' => [
        //             'path_segment',
        //             'import_id',
        //             'import_source'
        //         ],
        //         'subtypes' => [
        //             'tags' => [
        //                 'field' => 'tags',
        //                 'collector' => \PAGEmachine\Searchable\DataCollector\TcaRecord::class,
        //                 'config' => [
        //                 ]
        //             ],
        //             'categories' => [
        //                 'field' => 'categories',
        //                 'collector' => \PAGEmachine\Searchable\DataCollector\TcaRecord::class,
        //                 'config' => [
        //                     'excludeFields' => [
        //                         'items'
        //                     ]
        //                 ]
        //             ]
        //         ]
        //     ]
        // ],
        // 'styleguide' => [
        //     'indexer' => \PAGEmachine\Searchable\Indexer\TcaIndexer::class,
        //     'config' => [
        //         'type' => 'styleguide',
        //         'table' => 'tx_styleguide_forms',
        //         'subtypes' => [
        //             'select_25' => [
        //                 'config' => [
        //                     'field' => 'select_25',
        //                     'excludeFields' => [
        //                         'perms_userid',
        //                         'perms_groupid',
        //                         'perms_user',
        //                         'perms_group',
        //                         'perms_everybody',
        //                         'tx_impexp_origuid'
        //                     ]
        //                 ],
        //             ]
        //         ]

        //     ]

        // ]
    ],
];

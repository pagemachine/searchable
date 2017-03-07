<?php

if (!defined ('TYPO3_MODE')) die ('Access denied.');

use PAGEmachine\Searchable\Command\IndexCommandController;
if (TYPO3_MODE === 'BE') {
    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers']['searchable'] = IndexCommandController::class;
}

\TYPO3\CMS\Extbase\Utility\ExtensionUtility::configurePlugin(
    'PAGEmachine.' . $_EXTKEY,
    'Search',
    ['Search' => 'search, result'],
    ['Search' => 'search, result']
);

$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable'] = [
    'indices' => [
        0 => 'typo3'
    ],
    'types' => [
        'pages' => [
            'indexer' => \PAGEmachine\Searchable\Indexer\PagesIndexer::class,
            'config' => [
                'link' => [
                    'config' => [
                        'dynamicParts' => [
                            'pageUid' => 'uid'
                        ]
                    ]
                ]
            ]
        ],
        // 'news' => [
        //     'indexer' => \PAGEmachine\Searchable\Indexer\TcaIndexer::class,
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
        //                 'config' => [
        //                     'field' => 'tags'
        //                 ]
        //             ],
        //             'categories' => [
        //                 'config' => [
        //                     'field' => 'categories',
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
        //         'preview' => [
        //             'renderer' => \PAGEmachine\Searchable\Preview\FluidPreviewRenderer::class,
        //             'config' => [
        //                 'fields' => ['rte_1']
        //             ]
        //         ],
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
        //             ],
        //             'inline_2' => [
        //                 'config' => [
        //                     'field' => 'inline_2'
        //                 ]
        //             ]
        //         ]

        //     ]

        // ]
    ],
];

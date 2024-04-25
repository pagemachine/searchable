<?php

declare(strict_types=1);

use PAGEmachine\Searchable\Controller\BackendController;

return [
    'searchable' => [
        'parent' => 'web',
        'access' => 'user,group',
        'workspaces' => 'live',
        'labels' => 'LLL:EXT:searchable/Resources/Private/Language/locallang_mod.xlf',
        'iconIdentifier' => 'tx-searchable-main',
        'extensionName' => 'Searchable',
        'controllerActions' => [
            BackendController::class => [
                'start', 'search', 'request', 'resetIndices', 'indexFull', 'indexPartial',
            ],
        ],
    ],
];

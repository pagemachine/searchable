<?php

return [
    'frontend' => [
        'pagemachine/searchable/uri-builder' => [
            'target' => \PAGEmachine\Searchable\Middleware\UriBuilder::class,
            'before' => [
                'typo3/cms-frontend/static-route-resolver',
            ],
        ],
    ],
];

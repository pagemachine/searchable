<?php

return [
    'frontend' => [
        'fes/api/routing' => [
            'target' => \PAGEmachine\Searchable\Middleware\UriBuilder::class,
            'before' => [
                'typo3/cms-frontend/static-route-resolver',
            ],
        ],
    ],
];

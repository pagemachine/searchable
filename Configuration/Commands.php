<?php

return [
    'index:update:full' => [
        'class' => \PAGEmachine\Searchable\Command\Index\UpdateFullCommand::class,
    ],
    'index:update:partial' => [
        'class' => \PAGEmachine\Searchable\Command\Index\UpdatePartialCommand::class,
    ],
    'index:reset' => [
        'class' => \PAGEmachine\Searchable\Command\Index\ResetCommand::class,
    ],
    'index:setup' => [
        'class' => \PAGEmachine\Searchable\Command\Index\SetupCommand::class,
    ],
    'searchable:indexfull' => [
        'class' => \PAGEmachine\Searchable\Command\Legacy\LegacyUpdateFullCommand::class,
    ],
    'searchable:indexpartial' => [
        'class' => \PAGEmachine\Searchable\Command\Legacy\LegacyUpdatePartialCommand::class,
    ],
    'searchable:resetindex' => [
        'class' => \PAGEmachine\Searchable\Command\Legacy\LegacyResetCommand::class,
    ],
    'searchable:setup' => [
        'class' => \PAGEmachine\Searchable\Command\Legacy\LegacySetupCommand::class,
    ],
];

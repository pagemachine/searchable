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
];

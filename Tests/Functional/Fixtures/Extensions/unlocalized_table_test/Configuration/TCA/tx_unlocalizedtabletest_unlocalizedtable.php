<?php
defined('TYPO3') or die();

return [
    'ctrl' => [
        'title' => 'Unlocalized Table',
        'label' => 'title',
    ],
    'types' => [
        '0' => [
            'showitem' => 'title',
        ],
    ],
    'columns' => [
        'title' => [
            'config' => [
                'type' => 'input',
            ],
        ],
    ],
];

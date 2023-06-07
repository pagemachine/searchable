<?php
defined('TYPO3_MODE') or die();

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

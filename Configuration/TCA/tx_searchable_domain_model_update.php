<?php

return [
    'ctrl' => [
        'title' => 'Searchable Update Index',
        'hideTable' => true,
    ],
    'interface' => [
        'showRecordFieldList' => 'type,property,property_uid',
    ],
    'columns' => [
        'type' => [
            'exclude' => true,
            'label' => 'Type',
            'config' => [
                'type' => 'input',
                'size' => 255,
                'max' => 255,
                'eval' => 'trim',
                'readOnly' => true,
            ],
        ],
        'property' => [
            'exclude' => true,
            'label' => 'Property',
            'config' => [
                'type' => 'input',
                'size' => 255,
                'max' => 255,
                'eval' => 'trim',
                'readOnly' => true,
            ],
        ],
        'property_uid' => [
            'exclude' => true,
            'label' => 'Property UID',
            'config' => [
                'type' => 'input',
                'size' => 11,
                'eval' => 'int',
                'readOnly' => true,
            ],
        ],
    ],
    'types' => [
        '0' => [
            'showitem' => 'type, property, property_uid',
        ],
    ],
    'palettes' => [
        '1' => [
            'showitem' => '',
        ],
    ],
];

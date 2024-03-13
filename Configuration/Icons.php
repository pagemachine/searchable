<?php

declare(strict_types=1);

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'tx-searchable-main' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:searchable/Resources/Public/Icons/ext_icon.svg',
    ],
];

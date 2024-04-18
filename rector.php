<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php74\Rector\LNumber\AddLiteralSeparatorToNumberRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Set\ValueObject\LevelSetList;
use Ssch\TYPO3Rector\Set\Typo3SetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/Classes',
        __DIR__ . '/Tests',
    ])
    ->withSkip([
        __DIR__ . '/Tests/Functional/Fixtures/Extensions/*/Configuration/*',
    ])
    ->withImportNames(true, false, false, true)
    ->withPhpSets()
    ->withSets([
        LevelSetList::UP_TO_PHP_74,
        PHPUnitSetList::PHPUNIT_90,
        Typo3SetList::TYPO3_11,
    ])
    ->withSkip([
        AddLiteralSeparatorToNumberRector::class,
    ]);

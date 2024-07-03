<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Php71\Rector\FuncCall\RemoveExtraParametersRector;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Ssch\TYPO3Rector\Set\Typo3SetList;

return RectorConfig::configure()
    ->withPaths([
        __DIR__ . '/Classes',
        __DIR__ . '/Tests',
    ])
    ->withRootFiles()
    ->withSkip([
        __DIR__ . '/Tests/Functional/Fixtures/Extensions/*/Configuration/*',
    ])
    ->withImportNames(true, false, false, true)
    ->withPhpSets()
    ->withSets([
        PHPUnitSetList::PHPUNIT_90,
        Typo3SetList::TYPO3_11,
    ])
    ->withSkip([
        RemoveExtraParametersRector::class => [
            __DIR__ . '/Classes/DataCollector/TCA/FormDataRecord.php',
            __DIR__ . '/Classes/DataCollector/Utility/OverlayUtility.php',
        ],
    ]);

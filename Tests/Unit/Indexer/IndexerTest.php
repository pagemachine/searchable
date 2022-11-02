<?php
namespace PAGEmachine\Searchable\Tests\Unit\Indexer;

/*
 * This file is part of the Pagemachine Searchable project.
 */

use Nimut\TestingFramework\TestCase\UnitTestCase;
use PAGEmachine\Searchable\DataCollector\DataCollectorInterface;
use PAGEmachine\Searchable\Indexer\Indexer;
use PAGEmachine\Searchable\LinkBuilder\PageLinkBuilder;
use PAGEmachine\Searchable\Preview\DefaultPreviewRenderer;
use PAGEmachine\Searchable\Query\BulkQuery;
use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Testcase for PAGEmachine\Searchable\Indexer\Indexer
 */
class IndexerTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * Set up this testcase
     */
    protected function setUp(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['metaField'] = "_meta";
    }
    /**
     * Tear down this testcase
     */
    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
    }

    /**
     * @test
     */
    public function skipsEmptyRecordsFromDataCollector()
    {
        $dataCollector = $this->prophesize(DataCollectorInterface::class);
        $dataCollectorClassName = get_class($dataCollector->reveal());
        $config = [
            'collector' => [
                'className' => $dataCollectorClassName,
                'config' => ['collectorConfig'],
            ],
            'preview' => [
                'config' => ['previewConfig'],
            ],
            'link' => [
                'config' => ['linkConfig'],
            ],
        ];
        $query = $this->prophesize(BulkQuery::class);
        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager->get($dataCollectorClassName, ['collectorConfig'], 1)->willReturn($dataCollector->reveal());
        $objectManager->get(DefaultPreviewRenderer::class, ['previewConfig'])->willReturn($this->prophesize(DefaultPreviewRenderer::class)->reveal());
        $objectManager->get(PageLinkBuilder::class, ['linkConfig'])->willReturn($this->prophesize(PageLinkBuilder::class)->reveal());

        $this->indexer = new Indexer('test', 1, $config, $query->reveal(), $objectManager->reveal());

        $dataCollector->getRecords()->will(function () {
            yield [
                'uid' => 1,
            ];
            yield [];
            yield [
                'uid' => 3,
            ];
        });

        foreach ($this->indexer->run() as $overallCounter) {
            $this->assertEquals(2, $overallCounter);
        }
    }
}

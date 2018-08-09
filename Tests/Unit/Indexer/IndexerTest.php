<?php
namespace PAGEmachine\Searchable\Tests\Unit\Indexer;

/*
 * This file is part of the Pagemachine Searchable project.
 */

use PAGEmachine\Searchable\DataCollector\DataCollectorInterface;
use PAGEmachine\Searchable\Indexer\Indexer;
use PAGEmachine\Searchable\LinkBuilder\PageLinkBuilder;
use PAGEmachine\Searchable\Preview\DefaultPreviewRenderer;
use PAGEmachine\Searchable\Query\BulkQuery;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
 * Testcase for PAGEmachine\Searchable\Indexer\Indexer
 */
class IndexerTest extends UnitTestCase
{
    /**
     * Set up this testcase
     */
    protected function setUp()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['metaField'] = "_meta";
    }
    /**
     * Tear down this testcase
     */
    protected function tearDown()
    {
        GeneralUtility::purgeInstances();
    }

    /**
     * @test
     */
    public function skipsEmptyRecordsFromDataCollector()
    {
        /** @var DataCollectorInterface|\Prophecy\Prophecy\ObjectProphecy */
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
        /** @var BulkQuery|\Prophecy\Prophecy\ObjectProphecy */
        $query = $this->prophesize(BulkQuery::class);
        /** @var ObjectManager|\Prophecy\Prophecy\ObjectProphecy */
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

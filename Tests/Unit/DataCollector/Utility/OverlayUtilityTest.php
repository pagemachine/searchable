<?php
namespace PAGEmachine\Searchable\Tests\Unit\DataCollector\Utility;

/*
 * This file is part of the Pagemachine Searchable project.
 */

use PAGEmachine\Searchable\DataCollector\Utility\OverlayUtility;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Testcase for PAGEmachine\Searchable\DataCollector\Utility\OverlayUtility
 */
class OverlayUtilityTest extends UnitTestCase
{
    /**
     * @test
     * @dataProvider pagesToOverlay
     */
    public function performsPagesLanguageOverlay(array $page, $language, $overlayMode, $pageOverlay, $expected)
    {
        /** @var PageRepository|\Prophecy\Prophecy\ObjectProphecy */
        $pageRepository = $this->prophesize(PageRepository::class);
        $overlayUtility = $this->getMockBuilder(OverlayUtility::class)
            ->disableOriginalConstructor()
            ->setMethods()
            ->getMock();
        $this->inject($overlayUtility, 'pageRepository', $pageRepository->reveal());

        $pageRepository->getPageOverlay($page, $language)->willReturn($pageOverlay);

        $result = $overlayUtility->pagesLanguageOverlay($page, $language, $overlayMode);

        $this->assertEquals($expected, $result);
    }

    /**
     * @return array
     */
    public function pagesToOverlay()
    {
        return [
            'regular' => [
                ['uid' => 1, 'title' => 'default'],
                1,
                1,
                ['uid' => 1, 'pid' => 1, 'title' => 'translated'],
                ['uid' => 1, 'pid' => 1, 'title' => 'translated'],
            ],
            'overlay disabled' => [
                ['uid' => 1, 'title' => 'default'],
                1,
                0,
                ['uid' => 1, 'title' => 'default'],
                [],
            ],
        ];
    }
}

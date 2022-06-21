<?php
namespace PAGEmachine\Searchable\Tests\Unit\DataCollector\Utility;

/*
 * This file is part of the Pagemachine Searchable project.
 */

use Nimut\TestingFramework\TestCase\UnitTestCase;
use PAGEmachine\Searchable\DataCollector\Utility\OverlayUtility;
use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;

/**
 * Testcase for PAGEmachine\Searchable\DataCollector\Utility\OverlayUtility
 */
class OverlayUtilityTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * @test
     * @dataProvider pagesToOverlay
     */
    public function performsPagesLanguageOverlay(array $page, $language, $overlayMode, $pageOverlay, $expected)
    {
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

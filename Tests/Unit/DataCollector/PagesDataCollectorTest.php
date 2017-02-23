<?php
namespace PAGEmachine\Searchable\Tests\Unit\DataCollector;

use PAGEmachine\Searchable\DataCollector\PagesDataCollector;
use Prophecy\Argument;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
 * Testcase for PagesDataCollector
 */
class PagesDataCollectorTest extends UnitTestCase {

    /**
     * @var PagesDataCollector
     */
    protected $pagesDataCollector;

    /**
     * @var PageRepository
     */
    protected $pageRepository;

    /**
     * Set up this testcase
     */
    public function setUp() {

        $this->pagesDataCollector = new PagesDataCollector();

        $this->pageRepository = $this->prophesize(PageRepository::class);
        $this->inject($this->pagesDataCollector, "pageRepository", $this->pageRepository->reveal());
    }

    /**
     * @test
     */
    public function collectsPageListSingleLevel() {

        $pageList = [
            '3' => [
                'uid' => '3',
                'doktype' => '1',
                'title' => 'SimplePage'
            ]
        ];
        $this->pageRepository->getMenu(0, 'uid, doktype', 'sorting', '', false)
            ->willReturn($pageList);

        $this->pageRepository->getMenu(3, 'uid, doktype', 'sorting', '', false)
            ->willReturn([])
            ->shouldBeCalled();

        $this->assertEquals($pageList, $this->pagesDataCollector->getRecordList(0));
    }

    /**
     * @test
     */
    public function collectsPageListRecursive() {

        $pageList = [
            '3' => [
                'uid' => '3',
                'doktype' => '1',
                'title' => 'SimplePage'
            ]
        ];
        $subpageList = [
            '4' => [
                'uid' => '4',
                'doktype' => '1',
                'title' => 'SimpleSubpage'
            ]
        ];

        $returnList = [
            '3' => $pageList['3'],
            '4' => $subpageList['4']

        ];

        $this->pageRepository->getMenu(0, 'uid, doktype', 'sorting', '', false)
            ->willReturn($pageList);

        $this->pageRepository->getMenu(3, 'uid, doktype', 'sorting', '', false)
            ->willReturn($subpageList)
            ->shouldBeCalled();

        $this->pageRepository->getMenu(4, 'uid, doktype', 'sorting', '', false)
            ->willReturn([]);

        $this->assertEquals($returnList, $this->pagesDataCollector->getRecordList(0));
    }

  
}

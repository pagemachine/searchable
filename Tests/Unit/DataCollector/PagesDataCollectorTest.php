<?php
namespace PAGEmachine\Searchable\Tests\Unit\DataCollector;

use PAGEmachine\Searchable\DataCollector\PagesDataCollector;
use PAGEmachine\Searchable\DataCollector\TCA\FormDataRecord;
use Prophecy\Argument;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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
     * @var FormDataRecord
     */
    protected $formDataRecord;

    /**
     * Set up this testcase
     */
    public function setUp() {

        $GLOBALS['TCA']['pages'] = [
            'columns' => [
                'title' => [
                    'config' => [
                        'type' => 'input'
                    ]
                ]
            ]
        ];

        $this->pagesDataCollector = $this->getMockBUilder(PagesDataCollector::class)
        ->setConstructorArgs([PagesDataCollector::getDefaultConfiguration([], [])])
        ->setMethods([
                'getRecord'
            ])
        ->getMock();

        $this->pageRepository = $this->prophesize(PageRepository::class);
        $this->inject($this->pagesDataCollector, "pageRepository", $this->pageRepository->reveal());

        $this->formDataRecord = $this->prophesize(FormDataRecord::class);
        GeneralUtility::setSingletonInstance(FormDataRecord::class, $this->formDataRecord->reveal());
    }

    /**
     * @test
     */
    public function collectsPageListSingleLevel() {

        $pageList = [
            0 => [
                'uid' => '3',
                'pid' => '0',
                'doktype' => '1',
                'title' => 'SimplePage'
            ],
            1 => [
                'uid' => '4',
                'pid' => '0',
                'doktype' => '1',
                'title' => 'SimplePage2'
            ]
        ];

        $valueMap = [
            [3, $pageList[0]],
            [4, $pageList[1]]

        ];

        $this->pagesDataCollector->method("getRecord")
            ->will($this->returnValueMap($valueMap));

        $this->pageRepository->getMenu(0, 'uid', 'sorting', Argument::type("string"), false)->willReturn(['3' => [], '4' => []]);
        $this->pageRepository->getMenu(3, 'uid', 'sorting', Argument::type("string"), false)->willReturn([]);
        $this->pageRepository->getMenu(4, 'uid', 'sorting', Argument::type("string"), false)->willReturn([]);

        $records = $this->pagesDataCollector->getRecords();

        $this->assertEquals($pageList[0], $records->current());
        $records->next();
        $this->assertEquals($pageList[1], $records->current());


    }

    /**
     * @test
     */
    public function collectsPageListRecursive() {

        $pageList = [
            0 => [
                'uid' => '3',
                'doktype' => '1',
                'title' => 'SimplePage'
            ],
            1 => [
                'uid' => '4',
                'doktype' => '1',
                'title' => 'SimpleSubpage'
            ]
        ];

        $valueMap = [
            [3, $pageList[0]],
            [4, $pageList[1]]

        ];

        $this->pagesDataCollector->method("getRecord")
            ->will($this->returnValueMap($valueMap));

        $this->pageRepository->getMenu(0, 'uid', 'sorting', Argument::type("string"), false)->willReturn(['3' => []]);
        $this->pageRepository->getMenu(3, 'uid', 'sorting', Argument::type("string"), false)->willReturn(['4' => []]);
        $this->pageRepository->getMenu(4, 'uid', 'sorting', Argument::type("string"), false)->willReturn([]);

        $records = $this->pagesDataCollector->getRecords();

        $this->assertEquals($pageList[0], $records->current());
        $records->next();
        $this->assertEquals($pageList[1], $records->current());



    }

  
}

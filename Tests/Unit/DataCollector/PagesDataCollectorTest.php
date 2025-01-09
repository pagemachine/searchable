<?php
namespace PAGEmachine\Searchable\Tests\Unit\DataCollector;

use PAGEmachine\Searchable\DataCollector\PagesDataCollector;
use PAGEmachine\Searchable\DataCollector\TCA\FormDataRecord;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for PagesDataCollector
 */
class PagesDataCollectorTest extends UnitTestCase
{
    use ProphecyTrait;

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
    public function setUp(): void
    {
        parent::setUp();

        $this->resetSingletonInstances = true;

        $GLOBALS['TCA']['pages'] = [
            'ctrl' => [
                'enablecolumns' => [
                    'disabled' => 'hidden',
                ],
            ],
            'columns' => [
                'title' => [
                    'config' => [
                        'type' => 'input',
                    ],
                ],
            ],
        ];

        $this->pageRepository = $this->prophesize(PageRepository::class);
        GeneralUtility::addInstance(PageRepository::class, $this->pageRepository->reveal());

        $this->formDataRecord = $this->prophesize(FormDataRecord::class);
        GeneralUtility::setSingletonInstance(FormDataRecord::class, $this->formDataRecord->reveal());
    }

    protected function getPagesDataCollector($pid = 0)
    {
        $configuration = [
            'table' => 'pages',
            'pid' => $pid,
            'sysLanguageOverlay' => 1,
            'doktypes' => [
                PageRepository::DOKTYPE_DEFAULT,
            ],
            'transientDoktypes' => [
                PageRepository::DOKTYPE_LINK,
                PageRepository::DOKTYPE_SHORTCUT,
                PageRepository::DOKTYPE_SPACER,
            ],
            'groupWhereClause' => ' AND (pages.fe_group = "" OR pages.fe_group = 0)',
            'includeHideInMenu' => false,
            'mode' => 'whitelist',
            'fields' => [
                'title',
            ],
            'subCollectors' => [
            ],
        ];

        return $this->getMockBuilder(PagesDataCollector::class)
            ->setConstructorArgs([$configuration, 0])
            ->onlyMethods([
                'getRecord',
            ])
            ->getMock();
    }

    /**
     * @test
     */
    public function collectsPageListSingleLevel()
    {
        $pageList = [
            0 => [
                'uid' => '3',
                'pid' => '0',
                'doktype' => '1',
                'title' => 'SimplePage',
            ],
            1 => [
                'uid' => '4',
                'pid' => '0',
                'doktype' => '1',
                'title' => 'SimplePage2',
            ],
        ];

        $valueMap = [
            [3, $pageList[0]],
            [4, $pageList[1]],
        ];

        $pagesDataCollector = $this->getPagesDataCollector(0);

        $pagesDataCollector->method("getRecord")
            ->will($this->returnValueMap($valueMap));

        $this->pageRepository->getMenu(0, Argument::type("string"), 'sorting', Argument::type("string"))->willReturn(['3' => ['doktype' => '1'], '4' => ['doktype' => '1']]);
        $this->pageRepository->getMenu(3, Argument::type("string"), 'sorting', Argument::type("string"))->willReturn([]);
        $this->pageRepository->getMenu(4, Argument::type("string"), 'sorting', Argument::type("string"))->willReturn([]);

        GeneralUtility::addInstance(PageRepository::class, $this->pageRepository->reveal());

        $records = $pagesDataCollector->getRecords();

        $this->assertEquals($pageList[0], $records->current());
        $records->next();
        $this->assertEquals($pageList[1], $records->current());
    }

    /**
     * @test
     */
    public function collectsPageListRecursive()
    {
        $pageList = [
            0 => [
                'uid' => '3',
                'doktype' => '1',
                'title' => 'SimplePage',
            ],
            1 => [
                'uid' => '4',
                'doktype' => '1',
                'title' => 'SimpleSubpage',
            ],
        ];

        $valueMap = [
            [3, $pageList[0]],
            [4, $pageList[1]],
        ];

        $pagesDataCollector = $this->getPagesDataCollector(0);

        $pagesDataCollector->method("getRecord")
            ->will($this->returnValueMap($valueMap));

        $this->pageRepository->getMenu(0, Argument::type("string"), 'sorting', Argument::type("string"))->willReturn(['3' => ['doktype' => '1']]);
        $this->pageRepository->getMenu(3, Argument::type("string"), 'sorting', Argument::type("string"))->willReturn(['4' => ['doktype' => '1']]);
        $this->pageRepository->getMenu(4, Argument::type("string"), 'sorting', Argument::type("string"))->willReturn([]);

        GeneralUtility::addInstance(PageRepository::class, $this->pageRepository->reveal());

        $records = $pagesDataCollector->getRecords();

        $this->assertEquals($pageList[0], $records->current());
        $records->next();
        $this->assertEquals($pageList[1], $records->current());
    }

    /**
     * @test
     */
    public function collectsPageIncludingRoot()
    {
        $pageList = [
            0 => [
                'uid' => '3',
                'doktype' => '1',
                'title' => 'Root',
            ],
            1 => [
                'uid' => '4',
                'doktype' => '1',
                'title' => 'SimpleSubpage',
            ],
        ];

        $valueMap = [
            [3, $pageList[0]],
            [4, $pageList[1]],
        ];

        $pagesDataCollector = $this->getPagesDataCollector(3);

        $pagesDataCollector->method("getRecord")
            ->will($this->returnValueMap($valueMap));

        $this->pageRepository->getMenuForPages([3], Argument::type("string"), 'sorting', Argument::type("string"))->willReturn(['3' => ['doktype' => '1']]);
        $this->pageRepository->getMenu(0, Argument::type("string"), 'sorting', Argument::type("string"))->willReturn(['3' => ['doktype' => '1']]);
        $this->pageRepository->getMenu(3, Argument::type("string"), 'sorting', Argument::type("string"))->willReturn(['4' => ['doktype' => '1']]);
        $this->pageRepository->getMenu(4, Argument::type("string"), 'sorting', Argument::type("string"))->willReturn([]);

        GeneralUtility::addInstance(PageRepository::class, $this->pageRepository->reveal());

        $records = $pagesDataCollector->getRecords();

        $this->assertEquals($pageList[0], $records->current());
        $records->next();
        $this->assertEquals($pageList[1], $records->current());
    }
}

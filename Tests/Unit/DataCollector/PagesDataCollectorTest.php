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
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * Set up this testcase
     */
    public function setUp(): void
    {
        parent::setUp();

        $this->resetSingletonInstances = true;

        $GLOBALS['TCA']['pages'] = [
            'columns' => [
                'title' => [
                    'config' => [
                        'type' => 'input',
                    ],
                ],
            ],
        ];

        $configuration = [
            'table' => 'pages',
            'pid' => 0,
            'sysLanguageOverlay' => 1,
            'doktypes' => ['1'],
            'transientDoktypes' => ['4', '199'],
            'groupWhereClause' => ' AND (pages.fe_group = "" OR pages.fe_group = 0)',
            'includeHideInMenu' => false,
            'mode' => 'whitelist',
            'fields' => [
                'title',
            ],
            'subCollectors' => [
            ],
        ];

        $this->pagesDataCollector = $this->getMockBuilder(PagesDataCollector::class)
        ->onlyMethods([
            'getRecord',
        ])
        ->getMock();
        $this->pagesDataCollector->init($configuration, 0);

        $this->pageRepository = $this->prophesize(PageRepository::class);
        $this->pagesDataCollector->injectPageRepository($this->pageRepository->reveal());

        $this->formDataRecord = $this->prophesize(FormDataRecord::class);
        GeneralUtility::setSingletonInstance(FormDataRecord::class, $this->formDataRecord->reveal());
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

        $this->pagesDataCollector->method("getRecord")
            ->will($this->returnValueMap($valueMap));

        $this->pageRepository->getMenu(0, Argument::type("string"), 'sorting', Argument::type("string"))->willReturn(['3' => ['doktype' => '1'], '4' => ['doktype' => '1']]);
        $this->pageRepository->getMenu(3, Argument::type("string"), 'sorting', Argument::type("string"))->willReturn([]);
        $this->pageRepository->getMenu(4, Argument::type("string"), 'sorting', Argument::type("string"))->willReturn([]);

        $records = $this->pagesDataCollector->getRecords();

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

        $this->pagesDataCollector->method("getRecord")
            ->will($this->returnValueMap($valueMap));

        $this->pageRepository->getMenu(0, Argument::type("string"), 'sorting', Argument::type("string"))->willReturn(['3' => ['doktype' => '1']]);
        $this->pageRepository->getMenu(3, Argument::type("string"), 'sorting', Argument::type("string"))->willReturn(['4' => ['doktype' => '1']]);
        $this->pageRepository->getMenu(4, Argument::type("string"), 'sorting', Argument::type("string"))->willReturn([]);

        $records = $this->pagesDataCollector->getRecords();

        $this->assertEquals($pageList[0], $records->current());
        $records->next();
        $this->assertEquals($pageList[1], $records->current());
    }
}

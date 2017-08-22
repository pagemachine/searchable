<?php
namespace PAGEmachine\Searchable\Tests\Unit\Indexer;

use PAGEmachine\Searchable\DataCollector\PagesDataCollector;
use PAGEmachine\Searchable\Indexer\PagesIndexer;
use PAGEmachine\Searchable\LinkBuilder\LinkBuilderInterface;
use PAGEmachine\Searchable\Preview\PreviewRendererInterface;
use PAGEmachine\Searchable\Query\BulkQuery;
use Prophecy\Argument;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/**
* Testcase for PagesIndexer
*/
class PagesIndexerTest extends UnitTestCase
{
    /**
     * @var PagesIndexer
     */
    protected $pagesIndexer;

    /**
     * @var PagesDataCollector
     */
    protected $pagesCollector;

    /**
     * @var LinkBuilderInterface
     */
    protected $linkBuilder;

    /**
     * @var BulkQuery
     */
    protected $query;

    /**
     * Set up this testcase
     */
    public function setUp()
    {

        $this->query = $this->prophesize(BulkQuery::class);

        $this->query->execute()->willReturn("Success");

        $this->pagesCollector = $this->prophesize(PagesDataCollector::class);

        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager->get(PagesDataCollector::class, Argument::type("array"), 0)->willReturn($this->pagesCollector->reveal());

        $previewRenderer = $this->prophesize(PreviewRendererInterface::class);
        $previewRenderer->render(Argument::type("array"))->willReturn("<p>This is a preview!</p>");

        $this->linkBuilder = $this->prophesize(LinkBuilderInterface::class);

        $config = [
            'collector' => [
                'className' => PagesDataCollector::class,
                'config' => [
                    'pid' => 12,
                ],
            ],
        ];

        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['metaField'] = "_meta";

        $this->pagesIndexer = new PagesIndexer("typo3", 0, $config, $this->query->reveal(), $objectManager->reveal(), $previewRenderer->reveal(), $this->linkBuilder->reveal());
    }

    /**
     * @test
     */
    public function addsPagesToIndex()
    {

        $pageList = [
            [
                'uid' => '3',
                'doktype' => '1',
                'title' => 'SimplePage',
            ],
        ];

        $this->pagesCollector->getRecords()->willReturn($pageList);


        $this->linkBuilder->createLinksForBatch(Argument::type("array"))->willReturn([
            [
                'uid' => '3',
                'doktype' => '1',
                'title' => 'SimplePage',
                '_meta' => [
                    'preview' => '<p>This is a preview!</p>',
                    'renderedLink' => '<a href="fnsdk">foo</a>',
                ],
            ],
        ]);

        $this->query->addRows('uid', [
            [
                'uid' => '3',
                'doktype' => '1',
                'title' => 'SimplePage',
                '_meta' => [
                    'preview' => '<p>This is a preview!</p>',
                    'renderedLink' => '<a href="fnsdk">foo</a>',
                ],
            ],
        ])->shouldBeCalled();

        $this->query->execute()->shouldBeCalled();
        $this->query->resetBody()->shouldBeCalled();


        foreach ($this->pagesIndexer->run() as $runMessage) {
            //do nothing
        }
    }
}

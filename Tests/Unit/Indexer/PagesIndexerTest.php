<?php
namespace PAGEmachine\Searchable\Tests\Unit\Indexer;

use Elasticsearch\Client;
use PAGEmachine\Searchable\DataCollector\PagesDataCollector;
use PAGEmachine\Searchable\Indexer\PagesIndexer;
use PAGEmachine\Searchable\LinkBuilder\LinkBuilderInterface;
use PAGEmachine\Searchable\Preview\PreviewRendererInterface;
use PAGEmachine\Searchable\Query\BulkQuery;
use Prophecy\Argument;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Frontend\Page\PageRepository;

/**
* Testcase for PagesIndexer
*/
class PagesIndexerTest extends UnitTestCase {

    /**
     * @var PagesIndexer
     */
    protected $pagesIndexer;

    /**
     * @var PagesDataCollector
     */
    protected $pagesCollector;

    /**
     * @var BulkQuery
     */
    protected $query;

    /**
     * Set up this testcase
     */
    public function setUp() {

        $this->query = $this->prophesize(BulkQuery::class);

        $this->query->execute()->willReturn("Success");

        $this->pagesCollector = $this->prophesize(PagesDataCollector::class);

        $objectManager = $this->prophesize(ObjectManager::class);
        $objectManager->get(PagesDataCollector::class, Argument::type("array"), 0)->willReturn($this->pagesCollector->reveal());

        $previewRenderer = $this->prophesize(PreviewRendererInterface::class);
        $previewRenderer->render(Argument::type("array"))->willReturn("<p>This is a preview!</p>");

        $linkBuilder = $this->prophesize(LinkBuilderInterface::class);
        $linkBuilder->createLinkConfiguration(Argument::type("array"))->willReturn(["link" => "config"]);

        $config = [
            'pid' => 0,
            'collector' => [
                'className' => PagesDataCollector::class,
                'config' => []
            ]
        ];

        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['metaField'] = "_meta";

        $this->pagesIndexer = new PagesIndexer("typo3", 0, $config, $this->query->reveal(), $objectManager->reveal(), $previewRenderer->reveal(), $linkBuilder->reveal());
    }

    /**
     * @test
     */
    public function addsPagesToIndex() {

        $pageList = [
            '3' => [
                'uid' => '3',
                'doktype' => '1',
                'title' => 'SimplePage'
            ]
        ];

        $this->pagesCollector->getRecords(0)->willReturn($pageList);

        $this->query->addRow(3, [
                'uid' => '3',
                'doktype' => '1',
                'title' => 'SimplePage',
                '_meta' => [
                    'preview' => '<p>This is a preview!</p>',
                    'link' => ['link' => 'config']
                ]
            ])->shouldBeCalled();

        $this->query->execute()->shouldBeCalled();


        $this->pagesIndexer->run();

    }




}

<?php
namespace PAGEmachine\Searchable\Tests\Unit\Indexer;

use Elasticsearch\Client;
use PAGEmachine\Searchable\DataCollector\PagesDataCollector;
use PAGEmachine\Searchable\Indexer\PagesIndexer;
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
        $objectManager->get(PagesDataCollector::class, Argument::type("array"))->willReturn($this->pagesCollector->reveal());


        $this->pagesIndexer = new PagesIndexer("typo3", [], $this->query->reveal(), $objectManager->reveal());
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

        $this->pagesCollector->getRecordList()->willReturn($pageList);

        $this->pagesCollector->getRecord(3)->willReturn($pageList['3']);

        $this->query->addRow(3, [
                'uid' => '3',
                'doktype' => '1',
                'title' => 'SimplePage'
            ])->shouldBeCalled();

        $this->query->execute()->shouldBeCalled();


        $this->pagesIndexer->run();

    }




}

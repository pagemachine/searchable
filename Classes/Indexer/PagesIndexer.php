<?php
namespace PAGEmachine\Searchable\Indexer;

use Elasticsearch\Client;
use PAGEmachine\Searchable\Query\BulkQuery;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

class PagesIndexer extends Indexer {


    /**
     * @var PageRepository
     */
    protected $pageRepository;

    /**
     * @var string
     */
    public $contentIndexFields = "uid, header, bodytext";

    /**
     * Configuration array holding all options needed for this indexer
     *
     * @var array
     */
    protected $config  = [
        'type' => 'pages',
        'fields' => ["uid", "title", "doktype"],
        'subtypes' => []
    ];


    /**
     * Used while fetching pages, sorts out all page types which are not displayed in FE
     * This defines which page types are taken into account for recursive indexing, it does not mean they are all indexed!
     * The latter is defined by $indexedDoktypes.
     * 
     * @see https://docs.typo3.org/typo3cms/CoreApiReference/PageTypes/Index.html
     * @var string
     */
    public $doktypeLimiterStatement = " AND pages.doktype < 200";

    /**
     * Defines which doktypes are actually indexed.
     * @var array
     */
    public $indexedDoktypes = ['1'];

    /**
     * The array wrapper class holding all parameters
     * @var BulkQuery
     */
    protected $query;


    /**
     * @param String      $index  The index name to use
     * @param array       $config
     * @param Client|null $client
     */
    public function __construct($index, $config = [], Client $client = null, PageRepository $pageRepository = null) {

        parent::__construct($index, $config, $client);

        $this->pageRepository = $pageRepository ?: GeneralUtility::makeInstance(PageRepository::class);

    }

    /**
     * Main function for indexing
     * 
     * @todo Fix rootpage handling, currently fetches from id 0
     * @return array
     */
    public function run() {

        $this->query = new BulkQuery($this->index, $this->type);

        $this->collectDataFromPagetree(0);

        $response = $this->query->execute();

        return $response;
        

    }

    /**
     * Recursive function to collect all page/content data for indexing
     * 
     * @param  integer $id The current id (rootpage if first called)
     * @return void
     */
    protected function collectDataFromPagetree($id = 0) {

        $pageList = $this->pageRepository->getMenu($id, implode(",", $this->config['fields']), 'sorting', '', false);


        if (!empty($pageList)) {

            foreach ($pageList as $uid => $page) {

                if (in_array($page['doktype'], $this->indexedDoktypes)) {

                    $pageParams = $page;
                    $pageParams['content'] = $this->fetchContentForPage($uid);
                    $this->query->addRow($uid, $pageParams);
                }
                
                //Recursive call!
                $this->collectDataFromPagetree($uid);

            }
        }
    }

    /**
     * Fetches content for a given pageId
     * 
     * @param  integer $pageId
     * @return array
     */
    protected function fetchContentForPage($pageId) {

        $content = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($this->contentIndexFields, 'tt_content', 'pid = ' . $pageId . $this->pageRepository->enableFields('tt_content') . BackendUtility::deleteClause('tt_content'));

        return $content;

    }








}

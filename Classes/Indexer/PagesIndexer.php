<?php
namespace PAGEmachine\Searchable\Indexer;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

class PagesIndexer extends Indexer {


    /**
     * @param String      $index  The index name to use
     * @param String      $type   The type to use
     * @param Client|null $client
     */
    public function __construct($index, $type = 'pages', Client $client = null) {

        parent::__construct($index, $type, $client);
    }

    /**
     * Main function for indexing
     * 
     * @return array
     */
    public function run() {

        $indexFields = [];
        $indexFields['pages'] = 'uid, title';
        $indexFields['tt_content'] = 'uid, header, bodytext';

        $pageRepository = GeneralUtility::makeInstance(PageRepository::class);

        $pages = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($indexFields['pages'], 'pages', '1=1' . $pageRepository->enableFields('pages') . BackendUtility::deleteClause('pages'), $groupBy = '', $orderBy = '', $limit = '', $uidIndexField = '');

        $params = $this->prepareRequestParams();

        foreach ($pages as $page) {

            $params['body'][] = [
                'index' => [
                    '_index' => $this->index,
                    '_type' => $this->type,
                    '_id' => $page['uid']
                ]
            ];

            $pageParams = $page;
            $pageParams['content'] = [];

            $records = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows($indexFields['tt_content'], 'tt_content', 'pid = ' . $page['uid'] . $pageRepository->enableFields('tt_content') . BackendUtility::deleteClause('tt_content'), $groupBy = '', $orderBy = '', $limit = '', $uidIndexField = '');

            foreach ($records as $record){
                
                $pageParams['content'][] = $record;
            }
            $params['body'][] = $pageParams;
        }

        $response = $this->client->bulk($params);

        return $response;

    }

    /**
     * Returns prepared parameters
     * @return array
     */
    protected function prepareRequestParams() {
        return [
            'index' => $this->getIndex(),
            'type' => $this->getType(),
            'body' => []
        ];
    }








}

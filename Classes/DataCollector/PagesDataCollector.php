<?php
namespace PAGEmachine\Searchable\DataCollector;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Class for fetching pages data
 */
class PagesDataCollector extends TcaDataCollector implements DataCollectorInterface {

    protected $defaultConfiguration = [
        'excludeFields' => [
            'tstamp',
            'crdate',
            'cruser_id',
            't3ver_oid',
            't3ver_id',
            't3ver_wsid',
            't3ver_label',
            't3ver_state',
            't3ver_stage',
            't3ver_count',
            't3ver_tstamp',
            't3ver_move_id',
            't3_origuid',
            'editlock',
            'sys_language_uid',
            'l10n_parent',
            'l10n_diffsource',
            'deleted',
            'hidden',
            'starttime',
            'endtime',
            'sorting',
            'fe_group',
            'perms_userid',
            'perms_groupid',
            'perms_user',
            'perms_group',
            'doktype',
            'is_siteroot',
            'urltype',
            'shortcut',
            'layout',
            'url_scheme',
            'cache_timeout',
            'SYS_LASTCHANGED',
            'fe_login_mode',
            'backend_layout',
            'backend_layout_next_level'
        ]
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
     * @var string
     */
    protected $table = "pages";

    /**
     * Returns a list of page records
     * Recursive by default. Returns the whole page tree
     * 
     * @param  int $uid The uid to start from
     * @return array
     */
    public function getRecordList($pid = 0) {

        $recordList = $this->fetchPagesRecursive($pid);
        return $recordList;

    }

    /**
     * Fetches pages recursively from given root
     * 
     * @param  int $pid
     * @param  array  $pages The page array to append to
     * @return array $pages
     */
    protected function fetchPagesRecursive($pid, $pages = []) {

        $rawList = $this->pageRepository->getMenu($pid, 'uid, doktype', 'sorting', '', false);

        if (!empty($rawList)) {

            foreach ($rawList as $uid => $page) {

                if (in_array($page['doktype'], $this->indexedDoktypes)) {

                    $pages[$uid] = $page;
                }
                
                //Recursive call!
                $pages = $this->fetchPagesRecursive($uid, $pages);

            }
        }

        return $pages;

    }

    /**
     * Fetches a single record plus content
     * 
     * @param integer $identifier
     * @return array
     */
    public function getRecord($identifier) {

        $record = parent::getRecord($identifier);

        $record['content'] = $this->fetchContentForPage($record['uid']);

        return $record;
    }

    /**
     * Fetches content for a given pageId
     * 
     * @param  integer $pageId
     * @return array
     */
    protected function fetchContentForPage($pageId) {

        $content = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('header, bodytext', 'tt_content', 'pid = ' . $pageId . $this->pageRepository->enableFields('tt_content') . BackendUtility::deleteClause('tt_content'));

        return $content;

    }
}

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

    protected static $defaultConfiguration = [
        'table' => 'pages',
        'pid' => 0,
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
            'backend_layout_next_level',
            '_PAGES_OVERLAY',
            '_PAGES_OVERLAY_UID',
            '_PAGES_OVERLAY_LANGUAGE'
        ],
        'subCollectors' => [
            'content' => [
                'className' => \PAGEmachine\Searchable\DataCollector\TcaDataCollector::class,
                'config' => [
                    'field' => 'content',
                    'table' => 'tt_content',
                    'resolver' => [
                        'className' => \PAGEmachine\Searchable\DataCollector\RelationResolver\TtContentRelationResolver::class
                    ],
                    'excludeFields' => [
                        'sys_language_uid',
                        'l10n_parent',
                        'l10n_diffsource',
                        'deleted',
                        'hidden',
                        'starttime',
                        'endtime',
                        'sorting'
                    ]
                ]
            ]

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
     * 
     *
     * @return \Generator
     */
    public function getRecords($pid = null) {

        $pid = $pid ?: $this->config['pid'];

        $rawList = $this->pageRepository->getMenu($pid, 'uid, doktype', 'sorting', '', false);

        if (!empty($rawList)) {

            foreach ($rawList as $uid => $page) {

                yield $this->getRecord($uid);

                //@todo: use "yield from" as soon as PHP7 is a requirement
                $subpages = $this->getRecords($uid);

                if (!empty($subpages)) {

                    foreach ($subpages as $page) {

                        yield $page;
                    }                    
                }

            }
        }
    }

    /**
     *
     * Simplified languageOverlay mechanism for pages
     * pages_language_overlay contains a fixed set of OL fields. No need to run the FormEngine on them (does not work too well anyway)
     *
     * @param  array $record
     * @return array
     */
    protected function languageOverlay($record) {

        $overlayRecord = $this->pageRepository->getPageOverlay($record, $this->language);
        return $overlayRecord;
    }

}

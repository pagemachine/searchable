<?php
namespace PAGEmachine\Searchable\DataCollector;

use PAGEmachine\Searchable\DataCollector\Utility\OverlayUtility;
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
        'sysLanguageOverlay' => 1,
        'doktypes' => '1,4',
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
                        'tstamp',
                        'crdate',
                        'cruser_id',
                        'editlock',
                        'hidden',
                        'sorting',
                        'CType',
                        'rowDescription',
                        'image',
                        'imagewidth',
                        'imageorient',
                        'imagecols',
                        'imageborder',
                        'media',
                        'layout',
                        'deleted',
                        'cols',
                        'records',
                        'pages',
                        'starttime',
                        'endtime',
                        'colPos',
                        'fe_group',
                        'header_link',
                        'image_zoom',
                        'header_layout',
                        'menu_type',
                        'list_type',
                        'select_key',
                        'sectionIndex',
                        'linkToTop',
                        'file_collections',
                        'filelink_size',
                        'filelink_sorting',
                        'target',
                        'date',
                        'recursive',
                        'imageheight',
                        'sys_language_uid',
                        'tx_impexp_origuid',
                        'pi_flexform',
                        'accessibility_title',
                        'accessibility_bypass',
                        'accessibility_bypass_text',
                        'l18n_parent',
                        'l18n_diffsource',
                        'selected_categories',
                        'category_field',
                        'table_caption',
                        'table_delimiter',
                        'table_enclosure',
                        'table_header_position',
                        'table_tfoot',
                    ]
                ]
            ]

        ]
    ];

    /**
     * 
     *
     * @return \Generator
     */
    public function getRecords() {

        foreach ($this->getPageRecords($this->config['pid']) as $page) {

            yield $page;
        }
    }

    /**
     * 
     *
     * @return \Generator
     */
    protected function getPageRecords($pid = null) {

        $whereClause =
            ' AND pages.hidden = 0' .
            ' AND pages.doktype IN(' . $this->config['doktypes'] . ')'
            ;

        $rawList = $this->pageRepository->getMenu(
            $pid,
            'uid',
            'sorting',
            $whereClause,
            false
        );

        if (!empty($rawList)) {

            foreach ($rawList as $uid => $page) {

                yield $this->getRecord($uid);

                //@todo: use "yield from" as soon as PHP7 is a requirement
                $subpages = $this->getPageRecords($uid);

                if (!empty($subpages)) {

                    foreach ($subpages as $page) {

                        yield $page;
                    }                    
                }

            }
        }        
    }

    /**
     * Unset pid (works differently with pages and should not be taken into account)
     * @todo Check for rootline if we want to be extra precise
     *
     * @param  array $updateUidList
     * @return \Generator
     */
    public function getUpdatedRecords($updateUidList) {

        $this->config['pid'] = null;

        foreach (parent::getUpdatedRecords($updateUidList) as $record) {

            yield $record;
        }

    }

    /**
     * Get overlay
     *
     * @param  array $record
     * @return array
     */
    protected function languageoverlay($record) {

        return OverlayUtility::getInstance()->pagesLanguageOverlay($record, $this->language);
    }

}

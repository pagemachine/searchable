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
        'mode' => 'whitelist',
        'fields' => [
            'title'
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
                    'fields' => [
                        'header',
                        'subheader',
                        'bodytext'
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

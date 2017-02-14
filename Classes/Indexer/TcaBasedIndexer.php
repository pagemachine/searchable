<?php
namespace PAGEmachine\Searchable\Indexer;

use PAGEmachine\Searchable\DataCollector\TcaRecord;
use PAGEmachine\Searchable\Query\BulkQuery;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Simple TCA based indexer reading fields and processing them
 */
class TcaBasedIndexer extends Indexer {


    /**
     * The array wrapper class holding all parameters
     * @var BulkQuery
     */
    protected $query;

    /**
     *
     * @var \TYPO3\CMS\Frontend\Page\PageRepository
     * @inject
     */
    protected $pageRepository;

    /**
     * Configuration array holding all options needed for this indexer
     *
     * @var array
     */
    protected $config  = [
        'type' => '',
        'systemExcludeFields' => [
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
            'fe_group'
        ],
        'excludeFields' => [],
        'subtypes' => []
    ];

    /**
     * Main function for indexing
     * 
     * @return array
     */
    public function run() {

        $this->query = new BulkQuery($this->index, $this->type);

        $dataCollector = new TcaRecord($this->config);

        $recordUidList = $this->getRecordList();

        foreach ($recordUidList as $item) {

            $fullRecord = $dataCollector->getRecord($item['uid'], $this->config['table'], $this->config);

            $this->query->addRow($item['uid'], $fullRecord);
        }

        $response = $this->query->execute();

        return $response;

    }

    /**
     * Fetches records for indexing
     *
     * @return array
     */
    public function getRecordList() {

        $recordList = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
            "uid", 
            $this->config['table'], 
            "1=1" . $this->pageRepository->enableFields($this->config['table']) . BackendUtility::deleteClause($this->config['table'])
        );
        
        return $recordList;

    }








}

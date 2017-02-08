<?php
namespace PAGEmachine\Searchable\DataCollector;

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Class for fetching TCA-based data according to the given config
 */
class TcaBasedDataCollector {

    /**
     * @var PageRepository
     */
    protected $pageRepository;

    /**
     * Configuration
     *
     * @var array
     */
    protected $config = [];

    /**
     *
     * @param array $config
     * @param PageRepository|null $pageRepository
     */
    public function __construct($config = [], PageRepository $pageRepository = null) {

        $this->pageRepository = $pageRepository ?: GeneralUtility::makeInstance(PageRepository::class);
        $this->config = $config;
    }

    /**
     * Fetches records for indexing
     *
     * @return array
     */
    public function getRecords() {

        $records = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
            implode(",", $this->config['fields']), 
            $this->config['table'], 
            "1=1" . $this->pageRepository->enableFields($this->config['table']) . BackendUtility::deleteClause($this->config['table'])
        );

        return $records;

    }










}

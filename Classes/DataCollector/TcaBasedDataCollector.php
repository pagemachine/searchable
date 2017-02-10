<?php
namespace PAGEmachine\Searchable\DataCollector;

use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
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
     * TcaDatabaseRecord group (backend/form)
     *
     * @var TcaDatabaseRecord
     */
    protected $formDataGroup;

    /**
     *
     * @var FormDataCompiler
     */
    protected $formDataCompiler;

    /**
     * Configuration
     *
     * @var array
     */
    protected $config = [];

    protected $excludeFields = [];



    /**
     *
     * @param array $config
     * @param PageRepository|null $pageRepository
     */
    public function __construct($config = [], PageRepository $pageRepository = null, TcaDatabaseRecord $formDataGroup = null, FormDataCompiler $formDataCompiler = null) {

        $this->formDataGroup = $formDataGroup ?: GeneralUtility::makeInstance(TcaDatabaseRecord::class);
        $this->formDataCompiler = $formDataCompiler ?: GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);
        $this->pageRepository = $pageRepository ?: GeneralUtility::makeInstance(PageRepository::class);

        $this->config = $config;
    }

    /**
     * Fetches records for indexing
     *
     * @return array
     */
    public function getRecords() {

        $recordList = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
            "uid", 
            $this->config['table'], 
            "1=1" . $this->pageRepository->enableFields($this->config['table']) . BackendUtility::deleteClause($this->config['table'])
        );

        $records = [];
        foreach ($recordList as $item) {

            $records[] = $this->getSingleRecord($item['uid']);

        }
        
        return $records;

    }

    /**
     * Fetches a single record
     *
     * @return array
     */
    public function getSingleRecord($uid) {

        $formDataCompilerInput = [
            'tableName' => $this->config['table'],
            'vanillaUid' => $uid,
            'command' => 'edit'
        ];

        $data = $this->formDataCompiler->compile($formDataCompilerInput);

        $record = $data['databaseRow'];

        //@todo: Add field cleanup and subtype handling here



    }









}

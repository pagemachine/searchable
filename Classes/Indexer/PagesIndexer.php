<?php
namespace PAGEmachine\Searchable\Indexer;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

class PagesIndexer extends Indexer implements IndexerInterface {

    /**
     * @var array
     */
    protected static $defaultConfiguration = [
        'type' => 'pages',
        'collector' => [
            'className' => \PAGEmachine\Searchable\DataCollector\PagesDataCollector::class
        ],
        'link' => [
            'className' => \PAGEmachine\Searchable\LinkBuilder\PageLinkBuilder::class,
            'config' => [
                'titleField' => 'title', 
                'dynamicParts' => [
                    'pageUid' => 'uid'
                ]
            ]
        ],
        'mapping' => [
            'properties' => [
                'content' => [
                    'properties' => [
                        'header' => [
                            'type' => 'text'
                        ],
                        'bodytext' => [
                            'type' => 'text'
                        ]
                    ]
                ]
            ]
        ]
    ];

    /**
     * Main function for indexing
     * 
     * @todo Fix rootpage handling, currently fetches from id 0
     * @return array
     */
    public function run() {

        $pages = $this->dataCollector->getRecordList();
        
        foreach ($pages as $uid => $page) {

            $fullpage = $this->dataCollector->getRecord($uid);
            $fullpage = $this->addSystemFields($fullpage);
            
            $this->query->addRow($uid, $fullpage);
        }


        $response = $this->query->execute();
        return $response;
        

    }

    /**
     * Runs an update
     * 
     * @return void
     */
    public function runUpdate() {

        $recordUidList = $this->dataCollector->getUpdatedRecordList();

        \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($recordUidList, __METHOD__, 8, defined('TYPO3_cliMode'));
        die();


    }









}

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
        'collector' => [
            'className' => \PAGEmachine\Searchable\DataCollector\PagesDataCollector::class
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









}

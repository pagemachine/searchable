<?php
namespace PAGEmachine\Searchable\Indexer;


use PAGEmachine\Searchable\DataCollector\PagesDataCollector;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

class PagesIndexer extends Indexer {

    /**
     * Configuration array holding all options needed for this indexer
     *
     * @var array
     */
    protected $config  = [
        'type' => 'pages',
        'subtypes' => [
            'content' => [
                'collector' => \PAGEmachine\Searchable\DataCollector\TcaDataCollector::class,
                'config' => [
                    'field' => 'content',
                    'table' => 'tt_content',
                    'resolver' => \PAGEmachine\Searchable\DataCollector\RelationResolver\TtContentRelationResolver::class
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

        $dataCollector = $this->objectManager->get(PagesDataCollector::class, $this->config);

        $pages = $dataCollector->getRecordList();
        
        foreach ($pages as $uid => $page) {

            $fullpage = $dataCollector->getRecord($uid);
            $fullpage['preview'] = $this->previewRenderer->render($fullpage);
            
            $this->query->addRow($uid, $fullpage);
        }

        $response = $this->query->execute();
        return $response;
        

    }









}

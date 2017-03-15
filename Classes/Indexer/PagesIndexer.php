<?php
namespace PAGEmachine\Searchable\Indexer;


use PAGEmachine\Searchable\DataCollector\PagesDataCollector;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

class PagesIndexer extends Indexer implements IndexerInterface {

    /**
     * Configuration array holding all options needed for this indexer
     *
     * @var array
     */
    protected $config  = [
        'type' => 'pages',
        'table' => 'pages',
        'link' => [
            'config' => [
                'titleField' => 'title', 
                'dynamicParts' => [
                    'pageUid' => 'uid'
                ]
            ]
        ],
        'subtypes' => [
            'content' => [
                'collector' => \PAGEmachine\Searchable\DataCollector\TcaDataCollector::class,
                'config' => [
                    'field' => 'content',
                    'table' => 'tt_content',
                    'resolver' => \PAGEmachine\Searchable\DataCollector\RelationResolver\TtContentRelationResolver::class
                ]
            ],
            'categories' => [
                'collector' => \PAGEmachine\Searchable\DataCollector\TcaDataCollector::class,
                'config' => [
                    'field' => 'categories',
                    'excludeFields' => [
                        'items'
                    ]
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

        $dataCollector = $this->objectManager->get(PagesDataCollector::class, $this->config, $this->language);

        $pages = $dataCollector->getRecordList();
        
        foreach ($pages as $uid => $page) {

            $fullpage = $dataCollector->getRecord($uid);
            $fullpage = $this->addSystemFields($fullpage);
            
            $this->query->addRow($uid, $fullpage);
        }


        $response = $this->query->execute();
        return $response;
        

    }









}

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
        'pid' => 0,
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
     * @return array
     */
    public function run() {

        foreach ($this->dataCollector->getRecords($this->config['pid']) as $fullRecord) {

            $fullRecord = $this->addSystemFields($fullRecord);

            $this->query->addRow($fullRecord['uid'], $fullRecord);
        }

        $response = $this->query->execute();

        return $response;

    }








}

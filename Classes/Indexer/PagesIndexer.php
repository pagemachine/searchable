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
     * @return \Generator
     */
    public function run() {

        $counter = 0;
        $overallCounter = 0;

        foreach ($this->dataCollector->getRecords() as $fullRecord) {

            $fullRecord = $this->addSystemFields($fullRecord);

            $this->query->addRow($fullRecord['uid'], $fullRecord);

            $counter++;
            $overallCounter++;

            if ($counter >= 20) {

                $this->query->execute();
                $this->query->resetBody();

                $counter = 0;
                yield $overallCounter;
            }
        }

        if ($counter != 0) {

            $this->query->execute();
            $this->query->resetBody();
            yield $overallCounter;
        }

    }









}

<?php
namespace PAGEmachine\Searchable\Indexer;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * File Indexer - reads from sys_file_metadata and uses a pipeline to send over files as well
 */
class FileIndexer extends TcaIndexer
{
    /**
     * @var array
     */
    protected static $defaultConfiguration = [
        'pipeline' => 'attachment',
        'fileField' => 'file',
        'bulkSize' => 2,
        'collector' => [
            'className' => \PAGEmachine\Searchable\DataCollector\FileDataCollector::class,
        ],
        'link' => [
            'className' => \PAGEmachine\Searchable\LinkBuilder\FileLinkBuilder::class,
        ],
        'mapper' => [
            'className' => \PAGEmachine\Searchable\Mapper\DefaultMapper::class,
        ],
        'mapping' => [
            '_all' => [
                'store' => true,
            ],
        ],
    ];

    /**
     * Sends a batch
     *
     * @param  array $records
     * @return void
     */
    protected function sendBatch($records)
    {
        $records = $this->linkBuilder->createLinksForBatch($records);

        $resourceFactory = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance();

        foreach ($records as $key => $record) {
            if (!empty($record[$this->config['fileField']])) {
                foreach ($record[$this->config['fileField']] as $fileRecord) {
                    $file = $resourceFactory->getFileObject($fileRecord['uid']);

                    $records[$key]['attachments'][] = [
                        'filename' => $file->getProperty('name'),
                        'data' => base64_encode($file->getContents()),
                    ];
                }

                unset($records[$key][$this->config['fileField']]);
            }
        }

        $this->query->addRows('uid', $records);

        $this->query->execute();
        $this->query->resetBody();
    }
}

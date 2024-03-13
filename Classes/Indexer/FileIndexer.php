<?php
namespace PAGEmachine\Searchable\Indexer;

/*
 * This file is part of the PAGEmachine Searchable project.
 */
use PAGEmachine\Searchable\DataCollector\FileDataCollector;
use PAGEmachine\Searchable\LinkBuilder\FileLinkBuilder;
use PAGEmachine\Searchable\Mapper\DefaultMapper;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
            'className' => FileDataCollector::class,
        ],
        'link' => [
            'className' => FileLinkBuilder::class,
        ],
        'mapper' => [
            'className' => DefaultMapper::class,
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

        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);

        foreach ($records as $key => $record) {
            if (!empty($record[$this->config['fileField']])) {
                foreach ($record[$this->config['fileField']] as $fileRecord) {
                    $file = $resourceFactory->getFileObject($fileRecord['uid']);

                    try {
                        $records[$key]['attachments'][] = [
                            'filename' => $file->getProperty('name'),
                            'data' => base64_encode($file->getContents()),
                        ];
                    } catch (\Exception) {
                        // The actual file on disk does not exist for this file record.
                        // This should be logged, but for now we just skip it.
                        unset($records[$key]);
                        continue;
                    }
                }

                unset($records[$key][$this->config['fileField']]);
            }
        }
        if (!empty($records)) {
            $this->query->addRows('uid', $records);

            $this->query->execute();
            $this->query->resetBody();
        }
    }
}

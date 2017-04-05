<?php
namespace PAGEmachine\Searchable\Command;

use PAGEmachine\Searchable\IndexManager;
use PAGEmachine\Searchable\Service\ExtconfService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use \TYPO3\CMS\Extbase\Mvc\Controller\CommandController;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

class IndexCommandController extends CommandController
{

    /**
     * @var bool
     */
    protected $requestAdminPermissions = TRUE;

    /**
     * @var \PAGEmachine\Searchable\Indexer\IndexerFactory
     * @inject
     */
    protected $indexerFactory;

    /**
     * Reset all indices (if necessary) and let all defined indexers run
     * @return void
     */
    public function indexFullCommand() {

        $this->runIndexers(true);
    }

    /**
     * Runs an update on all indexers
     * @return void
     */
    public function indexPartialCommand() {

        $this->runIndexers(false);
    }

    /**
     * Resets the index for the given language. 
     * @param string $index
     * @return void
     */
    public function resetIndexCommand($language = null) {

        $this->outputLine();

        $indexers = $this->indexerFactory->makeIndexers();

        $mapping = [];

        foreach ($indexers as $indexer) {

            $mapping[$indexer->getType()] = $indexer->getMapping();
        }


        $indexManager = IndexManager::getInstance();

        if ($language != null) {

            $indexManager->resetIndex(ExtconfService::getIndex($language), $mapping);

            $this->outputLine("Index '" . ExtconfService::getIndex($language) . "' was successfully cleared.");
        }
        else {

            foreach (ExtconfService::getIndices() as $index) {

                $indexManager->resetIndex($index, $mapping);
                $this->outputLine("Index '" . $index . "' was successfully cleared.");
            }            
        }
    }

    /**
     * Runs indexers
     *
     * @param  boolean $full full update or partial update
     * @return void
     */
    protected function runIndexers($full = false) {

        $starttime = microtime(true);

        $indices = ExtconfService::getIndices();

        $this->outputLine();
        $this->outputLine("<info>Starting indexing, %s indices found.</info>", [count($indices)]);
        $this->outputLine("<info>Indexing mode: " . ($full ? "Full" : "Partial" . "</>"));

        $this->outputLine();

        foreach ($indices as $language => $index) {

            $this->indexLanguage($language, $full);
        }

        IndexManager::getInstance()->resetUpdateIndex();

        $endtime = microtime(true);

        $this->outputLine();
        $this->outputLine("<options=bold>Time (seconds):</> " . ($endtime - $starttime));
        $this->outputLine("<options=bold>Memory (MB):</> " . (memory_get_peak_usage(true) / 1000000));
        $this->outputLine();
        $this->outputLine("<info>Indexing finished.</info>");


    }

    /**
     * Runs the indexing process for one language
     *
     * @param  integer $language The language to index
     * @param  boolean $full full update or partial update
     * @return void
     */
    protected function indexLanguage($language = 0, $full = false) {

        $indexers = $this->indexerFactory->makeIndexers($language);

        if (!empty($indexers)) {

            $this->outputLine("<comment>Language %s:</comment>", [$language]);

            foreach ($indexers as $indexer) {

                $this->outputLine();
                $this->outputLine("<comment> Type '%s':</comment>", [$indexer->getType()] );
                $this->output->progressStart();

                if ($full) {

                    foreach ($indexer->run() as $resultMessage) {

                        $this->output->progressSet($resultMessage);
                    }                    
                } 
                else {

                    foreach ($indexer->runUpdate() as $resultMessage) {

                        $this->output->progressSet($resultMessage);

                    }   
                }
                $this->output->progressFinish();
                
            }
            $this->outputLine();
        } 
        else {

            $this->outputLine("<comment>WARNING: No indexers found for language " . $language . ". Doing nothing.</comment>");
        }

    }
}

<?php
namespace PAGEmachine\Searchable\Command;

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

        $starttime = microtime(true);

        $indices = ExtconfService::getIndices();

        foreach ($indices as $language => $indexname) {

            $this->indexLanguage($language);
        }

        $endtime = microtime(true);

        $this->outputLine("Time: " . ($endtime - $starttime));
        $this->outputLine("Memory (MB): " . (memory_get_peak_usage(true) / 1000000));

        $this->outputLine("Indexing finished.");

    }

    /**
     * Runs the indexing process for one language
     *
     * @param  integer $language The language to index
     * @return void
     */
    protected function indexLanguage($language = 0) {

        $indexers = $this->indexerFactory->makeIndexers($language);

        if (!empty($indexers)) {

            foreach ($indexers as $indexer) {

                $result = $indexer->run();

                if ($result['errors']) {

                    $this->outputLine("There was an error running " . $indexerConfiguration['indexer'] . ":");

                    \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($result, __METHOD__, 8);
                    die();
                }
            }

            $this->outputLine("Successfully ran indexing for language " . $language . ".");

        } else {

            $this->outputLine("WARNING: No indexers found for language " . $language . ". Doing nothing.");
        }

    }
}

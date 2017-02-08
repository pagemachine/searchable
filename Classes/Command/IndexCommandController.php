<?php
namespace PAGEmachine\Searchable\Command;

use PAGEmachine\Searchable\Service\ExtconfService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use \TYPO3\CMS\Extbase\Mvc\Controller\CommandController;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

class IndexCommandController extends CommandController
{

    /**
     * Reset all indices (if necessary) and let all defined indexers run
     * @return void
     */
    public function indexFullCommand() {

        $defaultIndex = ExtconfService::getIndex();

        $types = ExtconfService::getTypes();

        foreach ($types as $indexerConfiguration) {


            $indexer = GeneralUtility::makeInstance($indexerConfiguration['indexer'], $defaultIndex, $indexerConfiguration['config']);

            $result = $indexer->run();

            if ($result['errors']) {

                $this->outputLine("There was an error running " . $indexerConfiguration['indexer'] . ":");

                \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($result['errors'], __METHOD__, 5, true);
                break;
            }
        }

        $this->outputLine("Indexing finished.");

    }
}

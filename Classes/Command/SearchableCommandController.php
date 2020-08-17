<?php
namespace PAGEmachine\Searchable\Command;

use PAGEmachine\Searchable\Connection;
use PAGEmachine\Searchable\Indexer\IndexerInterface;
use PAGEmachine\Searchable\IndexManager;
use PAGEmachine\Searchable\PipelineManager;
use PAGEmachine\Searchable\Service\ExtconfService;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

class SearchableCommandController extends CommandController
{
    /**
     * @var bool
     */
    protected $requestAdminPermissions = true;

    /**
     * @var \PAGEmachine\Searchable\Indexer\IndexerFactory
     * @inject
     */
    protected $indexerFactory;

    /**
     * @var Dispatcher $signalDispatcher
     */
    protected $signalDispatcher;

    /**
     * @param Dispatcher $dispatcher
     */
    public function injectSignalDispatcher(Dispatcher $signalDispatcher)
    {
        $this->signalDispatcher = $signalDispatcher;
    }

    /**
     * Scheduled indexers, will be collected at start
     * @var array
     */
    protected $scheduledIndexers = [];

    /**
     * Indexing type, one of full, partial or schedule
     *
     * @var string
     */
    protected $indexingType;

    /**
     * Index type. If null, all indexers are run
     * @var string|null
     */
    protected $type = null;

    /**
     * Last time schedules where processed for indexing
     *
     * @var \DateTime
     */
    protected $lastScheduleProcessingDate;

    /**
     * Runs all indexers (full)
     * @param  string $type If set, only runs indexing for the given type
     * @return void
     */
    public function indexFullCommand($type = null)
    {
        $this->outputLine();
        $this->checkHealth();

        $this->indexingType = 'full';
        $this->type = $type;

        $this->collectScheduledIndexers();
        $this->runIndexers();
    }

    /**
     * Runs all indexers (updates only)
     * @param  string $type If set, only runs indexing for the given type
     * @return void
     */
    public function indexPartialCommand($type = null)
    {
        $this->outputLine();
        $this->checkHealth();

        $this->indexingType = 'partial';
        $this->type = $type;

        $this->collectScheduledIndexers();
        $this->runIndexers();
    }

    /**
     * Run all indexers (scheduled updates only)
     *
     * @param string $type If set, only runs indexing for the given type
     * @return void
     */
    public function indexSchedulesCommand($type = null)
    {
        $this->outputLine();
        $this->checkHealth();

        $this->indexingType = 'schedule';
        $this->type = $type;

        /** @var \TYPO3\CMS\Core\Registry */
        $registry = GeneralUtility::makeInstance(Registry::class);
        $this->lastScheduleProcessingDate = $registry->get('searchable', 'scheduleProcessingDate', new \DateTime('@0'));

        $this->collectScheduledIndexers();
        $this->runIndexers();

        $registry->set('searchable', 'scheduleProcessingDate', new \DateTime('@' . $GLOBALS['EXEC_TIME']));
    }

    /**
     * Reset index for one or all languages
     *
     * @param string $index
     * @return void
     */
    public function resetIndexCommand($language = null)
    {
        $this->outputLine();
        $this->checkHealth();

        $indexers = $this->indexerFactory->makeIndexers();

        $indexManager = IndexManager::getInstance();

        if ($language != null) {
            $indexManager->resetIndex(ExtconfService::getIndex($language));

            $this->outputLine("Index '" . ExtconfService::getIndex($language) . "' was successfully cleared.");
        } else {
            foreach (ExtconfService::getIndices() as $index) {
                $indexManager->resetIndex($index);
                $this->outputLine("Index '" . $index . "' was successfully cleared.");
            }
        }
    }

    /**
     * Sets up everything, needs to be run after installation.
     * Can be run multiple times to ensure correct setup.
     *
     * @return void
     */
    public function setupCommand()
    {
        $this->outputLine();
        $this->checkHealth();

        $indexManager = IndexManager::getInstance();
        $pipelineManager = PipelineManager::getInstance();

        $response = $indexManager->createIndex(
            ExtconfService::getInstance()->getUpdateIndex()
        );

        $this->outputLine("Checking for existing Update Index...");

        if (empty($response)) {
            $this->outputLine("<comment>\tUpdate Index already exists.</comment>");
        } else {
            $this->outputLine("<info>Update Index created.</info>");
        }

        $this->outputLine();
        $this->outputLine("Building defined indexers to validate configuration...");


        try {
            $indexers = $this->indexerFactory->makeIndexers();
        } catch (\Exception $e) {
            $this->outputline("<error>Something is wrong with your indexer configuration:</error>");
            $this->outputline(get_class($e));
            $this->outputline($e->getMessage());
            $this->outputLine();
            $this->outputLine("<error>Could not continue setup due to errors, aborting.</error>");

            return;
        }

        if (!empty($indexers)) {
            $this->outputLine("Done.");
        } else {
            $this->outputLine("<comment>\tWARNING: No indexers defined.</comment>");
        }

        $this->outputLine();
        $this->outputLine("Checking for existence of defined indices...");

        $indices = ExtconfService::getIndices();

        if (!empty($indices)) {
            foreach ($indices as $language => $index) {
                $response = $indexManager->createIndex($index);

                $this->outputLine("\tIndex '" . $index . "': " . (!empty($response) ? "<info>Created.</info>" : "<comment>Exists.</comment>"));
            }
        }

        //Create pipelines
        $this->outputLine();
        $this->output("Creating pipelines... ");
        $pipelineManager->createPipelines();
        $this->outputLine("<info>done</info>.");

        $this->outputLine();
        $this->outputLine("<info>Searchable setup finished.</info>");
    }

    /**
     * Collects scheduled indexers depending on settings
     * @return void
     */
    protected function collectScheduledIndexers()
    {
        $indices = ExtconfService::getIndices();

        foreach ($indices as $language => $index) {
            if ($this->type == null) {
                foreach ($this->indexerFactory->makeIndexers($language) as $indexer) {
                    $this->scheduledIndexers[$language][] = $indexer;
                }
            } else {
                $indexer = $this->indexerFactory->makeIndexer($language, $this->type);
                if ($indexer != null) {
                    $this->scheduledIndexers[$language][] = $indexer;
                }
            }
        }
    }

    /**
     * Runs indexers
     *
     * @return void
     */
    protected function runIndexers()
    {
        $starttime = microtime(true);

        $this->outputLine();
        $this->outputLine("<info>Starting indexing, %s indexers found.</info>", [count($this->scheduledIndexers[0])]);
        $this->outputLine("<info>Indexing mode: " . ($this->runFullIndexing ? "Full" : "Partial" . "</info>"));

        $this->outputLine();

        foreach ($this->scheduledIndexers as $language => $indexers) {
            if (!empty($indexers)) {
                $this->outputLine("<comment>Language %s:</comment>", [$language]);

                $environment = ExtconfService::getIndexEnvironment(ExtconfService::getIndex($language));
                $originalEnvironment = $this->applyEnvironment($environment);

                foreach ($indexers as $indexer) {
                    $this->runSingleIndexer($indexer);
                }

                $this->applyEnvironment($originalEnvironment);

                $this->outputLine();
            } else {
                $this->outputLine("<comment>WARNING: No indexers found for language %s. Doing nothing.</comment>", [$language]);
            }
        }

        if ($this->type == null) {
            IndexManager::getInstance()->resetUpdateIndex();
            $this->outputLine("<info>Update Index was reset.</info>");
        } else {
            $this->outputLine("<info>Keeping update index since not all types were updated.</info>");
        }

        $endtime = microtime(true);
        $elapsedTime = $endtime - $starttime;

        $this->outputLine();
        $this->outputLine("<options=bold>Time (seconds):</> " . $elapsedTime);
        $this->outputLine("<options=bold>Memory (MB):</> " . (memory_get_peak_usage(true) / 1000000));
        $this->outputLine();
        $this->outputLine("<info>Indexing finished.</info>");

        $this->signalDispatcher->dispatch(__CLASS__, 'afterIndexRun', [
            'fullIndexing' => $this->runFullIndexing,
            'elapsedTime' => $elapsedTime,
        ]);
    }

    /**
     * Runs a single indexer
     * @param  IndexerInterface $indexer
     * @param  bool          $full
     * @return void
     */
    protected function runSingleIndexer(IndexerInterface $indexer)
    {
        $this->outputLine();
        $this->outputLine("<comment> Type '%s':</comment>", [$indexer->getType()]);
        $this->output->progressStart();

        switch ($this->indexingType) {
            case 'full':
                $progressValues = $indexer->run();
                break;

            case 'partial':
                $progressValues = $indexer->runUpdate();
                break;

            case 'schedule':
                $currentExecutionDate = new \DateTime('@' . $GLOBALS['EXEC_TIME']);
                $progressValues = $indexer->runScheduleUpdate($this->lastScheduleProcessingDate, $currentExecutionDate);
                break;

            default:
                throw new \UnexpectedValueException(sprintf('Unexpected indexing type "%s"', $this->indexingType), 1518003689);
        }

        foreach ($progressValues as $progress) {
            $this->output->progressSet($progress);
        }

        $this->output->progressFinish();
    }

    /**
     * Apply the given environment, e.g. language and locale
     *
     * @param array $environment
     * @return array the original environment to be restored with another call
     */
    protected function applyEnvironment(array $environment)
    {
        $originalEnvironment = [];

        if (!empty($environment['language'])) {
            $originalEnvironment['language'] = $GLOBALS['BE_USER']->uc['lang'];
            $GLOBALS['BE_USER']->uc['lang'] = $environment['language'];
        }

        if (!empty($environment['locale'])) {
            $originalEnvironment['locale'] = setlocale(LC_ALL, 0);
            setlocale(LC_ALL, $environment['locale']);
        }

        return $originalEnvironment;
    }

    /**
     * Checks if ES is online
     *
     * @return void
     */
    protected function checkHealth()
    {
        if (!Connection::isHealthy()) {
            $this->outputLine("<error>Elasticsearch is offline, aborting.</error>");
            $this->quit();
        }
    }
}

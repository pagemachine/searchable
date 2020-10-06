<?php
declare(strict_types = 1);

namespace PAGEmachine\Searchable\Service;

use PAGEmachine\Searchable\Connection;
use PAGEmachine\Searchable\Indexer\IndexerFactory;
use PAGEmachine\Searchable\Indexer\IndexerInterface;
use PAGEmachine\Searchable\IndexManager;
use PAGEmachine\Searchable\PipelineManager;
use PAGEmachine\Searchable\Service\ExtconfService;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

final class IndexingService
{
    /**
     * @var IndexerFactory $indexerFactory
     */
    protected $indexerFactory;

    /**
     * @param IndexerFactory $indexerFactory
     */
    public function injectIndexerFactory(IndexerFactory $indexerFactory): void
    {
        $this->indexerFactory = $indexerFactory;
    }

    /**
     * @var Dispatcher $signalDispatcher
     */
    protected $signalDispatcher;

    /**
     * @param Dispatcher $signalDispatcher
     */
    public function injectSignalDispatcher(Dispatcher $signalDispatcher)
    {
        $this->signalDispatcher = $signalDispatcher;
    }

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param LogManager $logManager
     */
    public function injectLogManager(LogManager $logManager): void
    {
        $this->logger = $logManager->getLogger(__CLASS__);
    }

    /**
     * Scheduled indexers, will be collected at start
     *
     * @var array
     */
    protected $scheduledIndexers = [];

    /**
     * Determines if a full indexing is performed
     *
     * @var bool
     */
    protected $runFullIndexing = false;

    /**
     * Index type. If null, all indexers are run
     *
     * @var string|null
     */
    protected $type = null;

    /**
     * Sets up everything, needs to be run after installation.
     * Can be run multiple times to ensure correct setup.
     */
    public function setup(): void
    {
        $this->assertConnectionHealthy();

        $this->logger->debug('Checking for existing Update Index..');

        $indexManager = IndexManager::getInstance();
        $indexManager->createIndex(
            ExtconfService::getInstance()->getUpdateIndex()
        );
        $this->logger->debug('Ensured update index exists');

        try {
            $indexers = $this->indexerFactory->makeIndexers();
        } catch (\Exception $e) {
            $this->logger->error(sprintf(
                'Invalid indexers configuration: %s [%s]',
                $e->getMessage(),
                get_class($e)
            ));

            return;
        }

        if (empty($indexers)) {
            $this->logger->warning('No indexers defined');
        } else {
            $this->logger->debug('Successfully validated indexers configuration');
        }

        $indices = ExtconfService::getIndices();

        if (!empty($indices)) {
            foreach ($indices as $language => $index) {
                $indexManager->createIndex($index);

                $this->logger->debug(sprintf(
                    'Ensured index "%s" exists',
                    $index
                ));
            }
        }

        $pipelineManager = PipelineManager::getInstance();
        $pipelineManager->createPipelines();
        $this->logger->debug('Successfully created pipelines');
    }

    /**
     * Reset index for one or all languages
     *
     * @param int $language
     */
    public function resetIndex(int $language = null): void
    {
        $this->assertConnectionHealthy();

        $indexers = $this->indexerFactory->makeIndexers();
        $indexManager = IndexManager::getInstance();

        if ($language !== null) {
            $indexManager->resetIndex(ExtconfService::getIndex($language));

            $this->logger->info(sprintf(
                'Index "%s" was successfully cleared',
                ExtconfService::getIndex($language)
            ));
        } else {
            foreach (ExtconfService::getIndices() as $index) {
                $indexManager->resetIndex($index);

                $this->logger->info(sprintf(
                    'Index "%s" was successfully cleared',
                    $index
                ));
            }
        }
    }

    /**
     * Runs all indexers (full)
     *
     * @param string $type If set, only runs indexing for the given type
     */
    public function indexFull(string $type = null): void
    {
        $this->assertConnectionHealthy();

        $this->runFullIndexing = true;
        $this->type = $type;

        $this->collectScheduledIndexers();
        $this->runIndexers();
    }

    /**
     * Runs all indexers (updates only)
     *
     * @param string $type If set, only runs indexing for the given type
     */
    public function indexPartial(string $type = null): void
    {
        $this->assertConnectionHealthy();

        $this->runFullIndexing = false;
        $this->type = $type;

        $this->collectScheduledIndexers();
        $this->runIndexers();
    }

    /**
     * Collects scheduled indexers depending on settings
     */
    protected function collectScheduledIndexers(): void
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
     */
    protected function runIndexers(): void
    {
        $starttime = microtime(true);

        $this->logger->info(sprintf(
            'Starting "%s" indexing with %d indexers',
            $this->runFullIndexing ? 'full' : 'partial',
            count($this->scheduledIndexers[0])
        ));

        foreach ($this->scheduledIndexers as $language => $indexers) {
            if (!empty($indexers)) {
                $this->logger->debug(sprintf('Indexing language "%s"', $language));

                $environment = ExtconfService::getIndexEnvironment(ExtconfService::getIndex($language));
                $originalEnvironment = $this->applyEnvironment($environment);

                foreach ($indexers as $indexer) {
                    $this->runSingleIndexer($indexer);
                }

                $this->applyEnvironment($originalEnvironment);
            } else {
                $this->logger->warning(sprintf('No indexers found for language "%s", doing nothing', $language));
            }
        }

        if ($this->type === null) {
            IndexManager::getInstance()->resetUpdateIndex();
            $this->logger->info('Update index was reset');
        } else {
            $this->logger->notice('Keeping update index since not all types were updated');
        }

        $endtime = microtime(true);
        $elapsedTime = $endtime - $starttime;

        $this->logger->info('Indexing finished', [
            'elapsedTime' => $elapsedTime,
            'memoryUsage' => memory_get_peak_usage(true) / 1000000,
        ]);

        $this->signalDispatcher->dispatch(__CLASS__, 'afterIndexRun', [
            'fullIndexing' => $this->runFullIndexing,
            'elapsedTime' => $elapsedTime,
        ]);
    }

    /**
     * Runs a single indexer
     */
    protected function runSingleIndexer(IndexerInterface $indexer): void
    {
        $this->logger->debug(sprintf('Running indexer type "%s"', $indexer->getType()));

        if ($this->runFullIndexing) {
            foreach ($indexer->run() as $resultMessage) {
                $this->logger->debug(sprintf('Indexer type "%s" status: %s', $indexer->getType(), $resultMessage));
            }
        } else {
            foreach ($indexer->runUpdate() as $resultMessage) {
                $this->logger->debug(sprintf('Indexer type "%s" status: %s', $indexer->getType(), $resultMessage));
            }
        }
    }

    /**
     * Apply the given environment, e.g. language and locale
     *
     * @return array the original environment to be restored with another call
     */
    protected function applyEnvironment(array $environment): array
    {
        $originalEnvironment = [];

        if (!empty($environment['language'])) {
            $originalEnvironment['language'] = $GLOBALS['BE_USER']->uc['lang'];
            $GLOBALS['BE_USER']->uc['lang'] = $environment['language'];
        }

        if (!empty($environment['locale'])) {
            $originalEnvironment['locale'] = setlocale(LC_ALL, '0');
            setlocale(LC_ALL, $environment['locale']);
        }

        return $originalEnvironment;
    }

    /**
     * Checks if Elasticsearch is online
     *
     * @throws \RuntimeException if Elasticsearch is offline
     */
    protected function assertConnectionHealthy(): void
    {
        if (!Connection::isHealthy()) {
            $hosts = ExtconfService::getInstance()->getHostsSettings();

            throw new \RuntimeException(sprintf('Elasticsearch at "%s" is offline', implode(', ', $hosts)), 1599662577);
        }
    }

    public function __toString(): string
    {
        return __CLASS__;
    }
}

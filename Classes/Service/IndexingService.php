<?php
declare(strict_types = 1);

namespace PAGEmachine\Searchable\Service;

use PAGEmachine\Searchable\Connection;
use PAGEmachine\Searchable\Events\AfterIndexRunEvent;
use PAGEmachine\Searchable\Indexer\IndexerFactory;
use PAGEmachine\Searchable\Indexer\IndexerInterface;
use PAGEmachine\Searchable\IndexManager;
use PAGEmachine\Searchable\PipelineManager;
use PAGEmachine\Searchable\Service\ExtconfService;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

final class IndexingService implements \Stringable
{
    /**
     * @var IndexerFactory $indexerFactory
     */
    protected $indexerFactory;

    public function injectIndexerFactory(IndexerFactory $indexerFactory): void
    {
        $this->indexerFactory = $indexerFactory;
    }

    /**
     * @var PersistenceManagerInterface $persistenceManager
     */
    protected $persistenceManager;

    public function injectPersistenceManager(PersistenceManagerInterface $persistenceManager): void
    {
        $this->persistenceManager = $persistenceManager;
    }

    private EventDispatcherInterface $eventDispatcher;

    public function injectEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    public function injectLogManager(LogManager $logManager): void
    {
        $this->logger = $logManager->getLogger(self::class);
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
     * Index type. If empty, all indexers are run
     *
     * @var string
     */
    protected $type = '';

    /**
     * Sets up everything, needs to be run after installation.
     * Can be run multiple times to ensure correct setup.
     */
    public function setup(): void
    {
        $this->assertConnectionHealthy();

        $pipelineManager = PipelineManager::getInstance();
        $pipelineManager->createPipelines();
        $this->logger->debug('Successfully created pipelines');

        $this->logger->debug('Checking for existing Update Index..');

        $indexManager = IndexManager::getInstance();
        $indices = ExtconfService::getIndices();

        try {
            $indexers = [];

            foreach ($indices as $index) {
                $indexers[] = $this->indexerFactory->makeIndexerForIndex($index);
            }
        } catch (\Exception $e) {
            $this->logger->error(sprintf(
                'Invalid indexers configuration: %s [%s]',
                $e->getMessage(),
                $e::class
            ));

            return;
        }

        if (empty($indexers)) {
            $this->logger->warning('No indexers defined');
        } else {
            $this->logger->debug('Successfully validated indexers configuration');
        }

        if (!empty($indices)) {
            foreach ($indices as $index) {
                $indexManager->createIndex($index);

                $this->logger->debug(sprintf(
                    'Ensured index "%s" exists',
                    $index
                ));
            }
        }
    }

    /**
     * Reset index for one or all languages
     */
    public function resetIndex(int $language = null): void
    {
        $this->assertConnectionHealthy();

        $indexManager = IndexManager::getInstance();

        if ($language !== null) {
            $indices = ExtconfService::getIndicesByLanguage($language);
        } else {
            $indices = ExtconfService::getIndices();
        }

        foreach ($indices as $index) {
            $indexManager->resetIndex($index);

            $this->logger->info(sprintf(
                'Index "%s" was successfully cleared',
                $index
            ));
        }
    }

    /**
     * Runs all indexers (full)
     *
     * @param string $type If set, only runs indexing for the given type
     */
    public function indexFull(string $type = ''): void
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
    public function indexPartial(string $type = ''): void
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

        foreach ($indices as $index) {
            $indexerName = ExtconfService::getIndexerKeyOfIndex($index);

            if (empty($this->type) || $this->type == $indexerName) {
                $this->scheduledIndexers[$index][] = $this->indexerFactory->makeIndexerForIndex($index);
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
            'Starting "%s" indexing',
            $this->runFullIndexing ? 'full' : 'partial'
        ));

        foreach ($this->scheduledIndexers as $index => $indexers) {
            if (!empty($indexers)) {
                $this->logger->debug(sprintf('Indexing Index "%s"', $index));

                $environment = ExtconfService::getEnvironmentOfIndex($index);
                $restoreEnvironment = $this->applyEnvironment($environment);

                foreach ($indexers as $indexer) {
                    $this->runSingleIndexer($indexer);
                }

                $restoreEnvironment();
                $this->resetPersistence();
            } else {
                $this->logger->warning(sprintf('No indexers found with name "%s", doing nothing', $index));
            }
        }

        if (empty($this->type)) {
            IndexManager::getInstance()->resetUpdateIndex();
            $this->logger->info('Update index was reset');
        } else {
            $this->logger->notice('Keeping update index since not all types were updated');
        }

        $endtime = microtime(true);
        $elapsedTime = (int)($endtime - $starttime);

        $this->logger->info('Indexing finished', [
            'elapsedTime' => $elapsedTime,
            'memoryUsage' => memory_get_peak_usage(true) / 1000000,
        ]);

        $this->eventDispatcher->dispatch(new AfterIndexRunEvent(
            $this->runFullIndexing,
            $elapsedTime
        ));
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
     * @return \Closure callback to restore the original environment
     */
    protected function applyEnvironment(array $environment): \Closure
    {
        // Set environment language if BE_USER lang is not set (happens on CLI calls)
        if ($GLOBALS['BE_USER']->uc !== null) {
            $originalUserLanguage = $GLOBALS['BE_USER']->uc['lang'];
        } else {
            $originalUserLanguage = $environment['language'];
        }

        $originalLocale = setlocale(LC_ALL, '0');

        $GLOBALS['BE_USER']->uc['lang'] = $environment['language'];
        setlocale(LC_ALL, $environment['locale']);

        $restoreEnvironment = function () use ($originalUserLanguage, $originalLocale): void {
            $GLOBALS['BE_USER']->uc['lang'] = $originalUserLanguage;
            setlocale(LC_ALL, $originalLocale);
        };

        return $restoreEnvironment;
    }

    /**
     * Reset the Extbase persistence
     *
     * This is essential e.g. for retrieving objects once per language.
     */
    private function resetPersistence(): void
    {
        $this->persistenceManager->clearState();
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
        return self::class;
    }
}

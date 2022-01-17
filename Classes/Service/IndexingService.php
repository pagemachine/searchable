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
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\CMS\Core\Utility\DebugUtility;

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
     * @var PersistenceManagerInterface $persistenceManager
     */
    protected $persistenceManager;

    /**
     * @param PersistenceManagerInterface $persistenceManager
     */
    public function injectPersistenceManager(PersistenceManagerInterface $persistenceManager): void
    {
        $this->persistenceManager = $persistenceManager;
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

    private EventDispatcherInterface $eventDispatcher;

    public function injectEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
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
            foreach ($indices as $nameIndex => $index) {
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
     * Reset index for one or all
     *
     * 
     * @param string $nameIndex
     */
    public function resetIndex(string $nameIndex = ''): void
    {
        $this->assertConnectionHealthy();

        //$indexers = $this->indexerFactory->makeIndexers($nameIndex);
        $indexManager = IndexManager::getInstance();

        if ($nameIndex !== '') {
            $indexManager->resetIndex($nameIndex);

            $this->logger->info(sprintf(
                'Index "%s" was successfully cleared',
                $nameIndex
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

        foreach ($indices as $nameIndex => $index) {
            $language = ExtconfService::getIndexLanguage($index);
            $indexers = ExtconfService::getIndexIndexer($index);
            if (empty($this->type)) {
                foreach ($this->indexerFactory->makeIndexers($index, $language) as $indexer) {
                        $this->scheduledIndexers[$nameIndex][] = $indexer;
                }
            } else {
                if($this->type == $indexers){
                $indexer = $this->indexerFactory->makeIndexer($index, $language, $this->type);
                if ($indexer != null) {
                    $this->scheduledIndexers[$nameIndex][] = $indexer;
                }
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
            'Starting "%s" indexing with  indexers',
            $this->runFullIndexing ? 'full' : 'partial'
            //count($this->scheduledIndexers[0]) this doesnt work  Warning: count(): Parameter must be an array or an object that implements Countable
        ));

        foreach ($this->scheduledIndexers as $nameIndex => $indexers) {
            if (!empty($indexers)) {
                $this->logger->debug(sprintf('Indexing Index "%s"', $nameIndex));

                $environment = ExtconfService::getIndexEnvironment(ExtconfService::getIndex($nameIndex));
                $restoreEnvironment = $this->applyEnvironment((string)$nameIndex, $environment);

                foreach ($indexers as $indexer) {
                        $this->runSingleIndexer($indexer);
                }

                $restoreEnvironment();
                $this->resetPersistence();
            } else {
                $this->logger->warning(sprintf('No indexers found with name "%s", doing nothing', $nameIndex));
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

        $this->signalDispatcher->dispatch(__CLASS__, 'afterIndexRun', [
            $this->runFullIndexing,
            $elapsedTime,
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
    protected function applyEnvironment(string $nameIndex, array $environment): \Closure
    {
        $originalUserLanguage = $GLOBALS['BE_USER']->uc['lang'];
        $originalLocale = setlocale(LC_ALL, '0');

        $GLOBALS['BE_USER']->uc['lang'] = $environment['language'];
        setlocale(LC_ALL, $environment['locale']);

        $context = GeneralUtility::makeInstance(Context::class);
        $originalLanguageAspect = $context->getAspect('language');

        $restoreEnvironment = function () use ($originalUserLanguage, $originalLocale, $originalLanguageAspect): void {
            $GLOBALS['BE_USER']->uc['lang'] = $originalUserLanguage;
            setlocale(LC_ALL, $originalLocale);

            $context = GeneralUtility::makeInstance(Context::class);
            $originalLanguageAspect = $context->getAspect('language');

            $restoreEnvironment = function () use ($originalUserLanguage, $originalLocale, $originalLanguageAspect): void {
                $GLOBALS['BE_USER']->uc['lang'] = $originalUserLanguage;
                setlocale(LC_ALL, $originalLocale);

                $context = GeneralUtility::makeInstance(Context::class);
                $context->setAspect('language', $originalLanguageAspect);
            };

            $languageUid = ExtconfService::getIndexLanguage($nameIndex);

            $context->setAspect('language', new LanguageAspect($languageUid));
        } else { // TYPO3v8
            $restoreEnvironment = function () use ($originalUserLanguage, $originalLocale): void {
                $GLOBALS['BE_USER']->uc['lang'] = $originalUserLanguage;
                setlocale(LC_ALL, $originalLocale);
            };
        }

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
        return __CLASS__;
    }
}

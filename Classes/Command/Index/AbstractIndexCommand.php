<?php
declare(strict_types = 1);

namespace PAGEmachine\Searchable\Command\Index;

use PAGEmachine\Searchable\Service\IndexingService;
use Symfony\Component\Console\Command\Command;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

abstract class AbstractIndexCommand extends Command
{
    /**
     * @var IndexingService
     */
    protected $indexingService;

    public function __construct(...$arguments)
    {
        parent::__construct(...$arguments);

        $objectManager = GeneralUtility::makeInstance(ObjectManager::class);
        $this->indexingService = $objectManager->get(IndexingService::class);

        $GLOBALS['BE_USER']->initializeUserSessionManager();
    }
}

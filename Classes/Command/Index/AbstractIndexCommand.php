<?php
declare(strict_types = 1);

namespace PAGEmachine\Searchable\Command\Index;

use PAGEmachine\Searchable\Service\IndexingService;
use Symfony\Component\Console\Command\Command;

abstract class AbstractIndexCommand extends Command
{
    protected ?IndexingService $indexingService = null;

    public function injectIndexingService(IndexingService $indexingService)
    {
        $this->indexingService = $indexingService;
    }

    public function __construct(...$arguments)
    {
        parent::__construct(...$arguments);

        $GLOBALS['BE_USER']->initializeUserSessionManager();
    }
}

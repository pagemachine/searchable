<?php
declare(strict_types = 1);

namespace PAGEmachine\Searchable\Command\Index;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class ResetCommand extends AbstractIndexCommand
{
    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setDescription('Reset search index');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->indexingService->resetIndex();

        return 0;
    }
}

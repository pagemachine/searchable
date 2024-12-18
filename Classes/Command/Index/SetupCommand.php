<?php
declare(strict_types = 1);

namespace PAGEmachine\Searchable\Command\Index;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class SetupCommand extends AbstractIndexCommand
{
    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setDescription('Set up everything for search')
            ->setHelp('Sets up indices and pipelines and verifies the indexer configuration, needs to be run after installation. Can be run multiple times to ensure correct setup.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->indexingService->setup();

        return 0;
    }
}

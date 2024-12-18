<?php
declare(strict_types = 1);

namespace PAGEmachine\Searchable\Command\Index;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class UpdateFullCommand extends AbstractIndexCommand
{
    /**
     * Configures the current command.
     */
    protected function configure()
    {
        $this
            ->setDescription('Process search indexers')
            ->addArgument('type', InputArgument::OPTIONAL, 'Type to run indexers for, all by default');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->indexingService->indexFull($input->getArgument('type') ?: '');

        return 0;
    }
}

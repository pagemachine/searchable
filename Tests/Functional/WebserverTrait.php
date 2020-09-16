<?php
declare(strict_types = 1);

namespace PAGEmachine\Searchable\Tests\Functional;

use Symfony\Component\Process\Process;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;

trait WebserverTrait
{
    private function startWebserver(): void
    {
        $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
        $serverProcess = new Process(
            [
                PHP_BINARY,
                '-S',
                'localhost:8080',
            ],
            $this->getInstancePath(),
            [
                'TYPO3_PATH_APP' => $this->getInstancePath(),
                'TYPO3_PATH_ROOT' => $this->getInstancePath(),
            ]
        );
        $serverProcess->start(function (string $type, string $output) use ($logger): void {
            if ($type === Process::ERR) {
                $logger->error($output);
            } else {
                $logger->info($output);
            }
        });
    }
}

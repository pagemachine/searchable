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
                'TYPO3_PATH_APP' => getenv('TYPO3_PATH_APP'),
                'TYPO3_PATH_ROOT' => getenv('TYPO3_PATH_ROOT'),
            ]
        );
        $serverProcess->start(function (string $type, string $output) use ($logger): void {
            if ($type === Process::ERR) {
                $logger->error($output);
            } else {
                $logger->info($output);
            }
        });

        $this->waitForServer('localhost', 8080);
    }

    private function waitForServer(string $host, int $port): void
    {
        for ($i = 0; $i < 60; $i++) {
            $socket = fsockopen($host, $port);

            if ($socket !== false) {
                break;
            }

            // 50ms per try
            usleep(50000);
        }
    }
}

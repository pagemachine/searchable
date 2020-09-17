<?php
declare(strict_types = 1);

namespace PAGEmachine\Searchable\Tests\Functional;

use Symfony\Component\Process\Process;

trait WebserverTrait
{
    /**
     * @var Process
     */
    private $serverProcess;

    protected function startWebserver(): void
    {
        $this->serverProcess = new Process(
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
        $this->serverProcess->start();

        $this->waitForServer('localhost', 8080);
    }

    protected function stopWebserver(): void
    {
        $this->serverProcess->stop(0);
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

<?php
declare(strict_types = 1);

namespace PAGEmachine\Searchable\Events;

final class AfterIndexRunEvent
{
    private bool $fullIndexing;

    private int $elapsedTime;

    public function __construct(
        bool $fullIndexing,
        int $elapsedTime
    ) {
        $this->fullIndexing = $fullIndexing;
        $this->elapsedTime = $elapsedTime;
    }

    public function isFullIndexing(): bool
    {
        return $this->fullIndexing;
    }

    public function getElapsedTime(): int
    {
        return $this->elapsedTime;
    }
}

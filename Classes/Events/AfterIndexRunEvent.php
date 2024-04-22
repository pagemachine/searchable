<?php
declare(strict_types = 1);

namespace PAGEmachine\Searchable\Events;

final class AfterIndexRunEvent
{
    public function __construct(private bool $fullIndexing, private int $elapsedTime)
    {
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

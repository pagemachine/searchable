<?php
declare(strict_types = 1);

namespace PAGEmachine\Searchable\Events;

final class AfterIndexRunEvent
{
    public function __construct(private readonly bool $fullIndexing, private readonly int $elapsedTime)
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

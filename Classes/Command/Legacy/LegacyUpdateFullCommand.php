<?php
declare(strict_types = 1);

namespace PAGEmachine\Searchable\Command\Legacy;

final class LegacyUpdateFullCommand extends AbstractLegacyCommand
{
    /**
     * @var string
     */
    protected $replacementCommand = 'index:update:full';
}

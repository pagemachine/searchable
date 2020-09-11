<?php
declare(strict_types = 1);

namespace PAGEmachine\Searchable\Command\Legacy;

final class LegacyResetCommand extends AbstractLegacyCommand
{
    /**
     * @var string
     */
    protected $replacementCommand = 'index:reset';
}

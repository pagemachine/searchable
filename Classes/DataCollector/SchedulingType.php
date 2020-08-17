<?php
namespace PAGEmachine\Searchable\DataCollector;

/*
 * This file is part of the Pagemachine Searchable project.
 */

use TYPO3\CMS\Core\Type\Enumeration;

/**
 * Enumeration of record scheduling types
 */
final class SchedulingType extends Enumeration
{
    /**
     * Records which have been activated: their start date was passed
     */
    const ACTIVATED = 1;

    /**
     * Records which have expired: their end date has passed
     */
    const EXPIRED = 2;
}

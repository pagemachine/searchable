<?php
namespace PAGEmachine\Searchable\DataCollector;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

interface ScheduleAwareDataCollectorInterface
{
     /**
     * Fetches the list of records scheduled (expired/activated) in a date range
     *
     * @param \DateTime $startDate the start of the date range
     * @param \DateTime $endTime the end of the date range
     * @param SchedulingType $type the scheduling type
     * @return \Traversable
     */
    public function getScheduledRecords(\DateTime $startDate, \DateTime $endDate, SchedulingType $type);
}

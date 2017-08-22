<?php
namespace PAGEmachine\Searchable\LinkBuilder;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

interface LinkBuilderInterface
{
    /**
     * Creates a link
     *
     * @param  array $record
     * @return string
     */
    public function createLinkConfiguration($record);


    /**
     * Creates links for a batch of records
     *
     * @param  array $records
     * @return array $records
     */
    public function createLinksForBatch($records);
}

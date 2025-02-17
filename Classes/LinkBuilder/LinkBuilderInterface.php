<?php
namespace PAGEmachine\Searchable\LinkBuilder;

/*
 * This file is part of the Pagemachine Searchable project.
 */

interface LinkBuilderInterface
{
    /**
     * Creates a link
     *
     * @param  array $record
     * @param  int   $language
     * @return array
     */
    public function createLinkConfiguration($record, $language);

    /**
     * Creates links for a batch of records
     *
     * @param  array $records
     * @param  int   $language
     * @return array $records
     */
    public function createLinksForBatch($records, $language = 0);
}

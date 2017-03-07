<?php
namespace PAGEmachine\Searchable\LinkBuilder;


/*
 * This file is part of the PAGEmachine Searchable project.
 */

interface LinkBuilderInterface {

    /**
     * Creates a link
     * 
     * @param  array $record
     * @return string
     */
    public function createLinkConfiguration($record);

}

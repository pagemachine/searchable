<?php
namespace PAGEmachine\Searchable\Mapper;

use PAGEmachine\Searchable\Indexer\IndexerInterface;


/*
 * This file is part of the PAGEmachine Searchable project.
 */

interface MapperInterface {

    /**
     * Creates a mapping array for the given index
     *
     * @param  IndexerInterface $indexer
     * @return array $mapping
     */
    public function createMapping(IndexerInterface $indexer);

}

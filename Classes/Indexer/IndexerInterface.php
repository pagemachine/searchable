<?php

namespace PAGEmachine\Searchable\Indexer;

/*
 * This file is part of the Pagemachine Searchable project.
 */

interface IndexerInterface
{
    /**
     * Main function for indexing
     *
     * @return \Generator
     */
    public function run();

    /**
    * Update function for indexing
    *
    * @return \Generator
    */
    public function runUpdate();

    /**
     * @return string
     */
    public function getIndex();

    /**
     * @param string $index
     */
    public function setIndex($index);

    /**
     * @return string
     */
    public function getType();

    /**
     * @param string $type
     */
    public function setType($type);

    /**
     * @return int
     */
    public function getLanguage();

    /**
     * @param int $language
     */
    public function setLanguage($language);

    /**
     * @return array
     */
    public function getConfig();

    /**
     * @param array $config
     */
    public function setConfig($config);
}

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
     * @return String
     */
    public function getIndex();

    /**
     * @param String $index
     * @return void
     */
    public function setIndex($index);

    /**
     * @return String
     */
    public function getType();

    /**
     * @param String $type
     * @return void
     */
    public function setType($type);

    /**
     * @return int
     */
    public function getLanguage();

    /**
     * @param int $language
     * @return void
     */
    public function setLanguage($language);

    /**
     * @return array
     */
    public function getConfig();

    /**
     * @param array $config
     * @return void
     */
    public function setConfig($config);
}

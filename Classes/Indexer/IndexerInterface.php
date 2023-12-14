<?php
namespace PAGEmachine\Searchable\Indexer;

/*
 * This file is part of the PAGEmachine Searchable project.
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
     * Returns the name of the index in elasticsearch "<config_index>_<indexer>"
     *
     * @return String
     */
    public function getIndex();

    /**
     * @param String $index
     * @return void
     */
    public function setIndex($index);

    /**
     * Returns the indexer name. Usually the key of the indexer entry in the configuration array
     *
     * @return String
     */
    public function getType();

    /**
     * @param String $type
     * @return void
     */
    public function setType($type);

    /**
     * Returns the language that should be indexed
     *
     * @return int
     */
    public function getLanguage();

    /**
     * @param int $language
     * @return void
     */
    public function setLanguage($language);

    /**
     * Returns the configuration array for the indexer
     *
     * @return array
     */
    public function getConfig();

    /**
     * @param array $config
     * @return void
     */
    public function setConfig($config);
}

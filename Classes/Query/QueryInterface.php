<?php
namespace PAGEmachine\Searchable\Query;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * QueryInterface
 */
interface QueryInterface
{
    /**
     * @return array
     */
    public function getParameters();

    /**
     * @param array $parameters
     * @return QueryInterface
     */
    public function setParameters($parameters);

    /**
     * @return string
     */
    public function getTerm();

    /**
     * @param string $term
     * @return QueryInterface
     */
    public function setTerm($term);

    /**
     * @param array $indices
     */
    public function setIndices($indices);

    /**
     * @return array
     */
    public function getIndices();

    /**
     * @param string $index
     * @return QueryInterface
     */
    public function addIndex($index);

    /**
     * @param string $index
     * @return QueryInterface
     */
    public function removeIndex($index);
}

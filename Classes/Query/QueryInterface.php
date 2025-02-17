<?php
namespace PAGEmachine\Searchable\Query;

/*
 * This file is part of the Pagemachine Searchable project.
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
    public function setIndices(array $indices): void;

    /**
     * @return array
     */
    public function getIndices(): array;

    /**
     * @param string $index
     * @return QueryInterface
     */
    public function addIndex(string $index): QueryInterface;

    /**
     * @param string $index
     * @return QueryInterface
     */
    public function removeIndex(string $index): QueryInterface;
}

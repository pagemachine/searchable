<?php
namespace PAGEmachine\Searchable\Query;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * QueryInterface
 */
interface QueryInterface {

    /**
     * @return array
     */
    public function getParameters();
    
    /**
     * @param array $parameters
     * @return void
     */
    public function setParameters($parameters);

    /**
     * @return string
     */
    public function getTerm();
    
    /**
     * @param string $term
     * @return SearchQuery
     */
    public function setTerm($term);
}

<?php
namespace PAGEmachine\Searchable\Query;

use PAGEmachine\Searchable\LanguageIdTrait;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Query class for searching
 */
class AutosuggestQuery extends SearchQuery implements QueryInterface
{
    use LanguageIdTrait;

    /**
     * @var string $term
     */
    protected $term = "";

    /**
     * @return string
     */
    public function getTerm()
    {
        return $this->term;
    }

    /**
     * @param string $term
     * @return AutosuggestQuery
     */
    public function setTerm($term)
    {
        $this->term = $term;
        return $this;
    }

    /**
     * Builds the query
     *
     * @return void
     */
    protected function build()
    {
        $this->parameters['body'] = [
        ];

        $this->setIndices($this->getActiveIndices());
        $this->applyFeatures();
    }
}

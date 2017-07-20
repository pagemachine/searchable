<?php
namespace PAGEmachine\Searchable\Eid;

use PAGEmachine\Searchable\Query\SearchQuery;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Eid-based search class
 */
class Search extends AbstractEidHandler {

    /**
     * Returns results for given term
     *
     * @param  string $term
     * @return array $suggestions
     */
    protected function getResults($term)
    {

        $query = GeneralUtility::makeInstance(SearchQuery::class);

        $query
            ->setTerm($term);

        $result = $query->execute();

        return $result['hits']['hits'];
    }

}

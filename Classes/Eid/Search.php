<?php
namespace PAGEmachine\Searchable\Eid;

use PAGEmachine\Searchable\Query\SearchQuery;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Eid-based search class
 */
class Search extends AbstractEidHandler
{
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
            ->setTerm($term)
            ->setPage($this->options['page'] ?: 1);

        $result = $query->execute();

        return [
            'results' => $result,
            'totalPages' => $query->getPageCount(),
            'currentPage' => $this->options['page'],
        ];
    }
}

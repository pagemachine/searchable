<?php
namespace PAGEmachine\Searchable\Eid;

use PAGEmachine\Searchable\Query\AutosuggestQuery;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Eid-based autosuggest class
 */
class Autosuggest extends AbstractEidHandler
{
    /**
     * Returns results for given term
     *
     * @param  string $term
     * @return array $suggestions
     */
    protected function getResults($term)
    {
        $query = GeneralUtility::makeInstance(AutosuggestQuery::class);

        $query
            ->setTerm($term);

        $result = $query->execute();

        $suggestions = [];

        if (!empty($result['suggest']['searchable_autosuggest'][0]['options'])) {
            foreach ($result['suggest']['searchable_autosuggest'][0]['options'] as $suggestion) {
                $suggestions[] = $suggestion['text'];
            }
        }

        return ['suggestions' => $suggestions];
    }
}

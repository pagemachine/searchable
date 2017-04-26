<?php
namespace PAGEmachine\Searchable\Controller;

use PAGEmachine\Searchable\Search;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

class SearchController extends ActionController {

    /**
     * Renders the search form
     *
     * @param string $term
     * @return void
     */
    public function searchbarAction($term = null) {

        $this->view->assign("settings", $this->settings);
        $this->view->assign("term", $term);
    }

    /**
     * Performs a search and returns the result
     * 
     * @param string $term
     * @param int $page
     * @return void
     */
    public function resultsAction($term = null, $page = 1) {

        //Pagination Offset
        $options['from'] = (int)($page - 1) * $this->settings['search']['resultsPerPage'];

        //Pagination Size
        $options['size'] = (int)$this->settings['search']['resultsPerPage'];

        if ($term) {
            $result = Search::getInstance()->search($term, $options);
        }

        $totalPages = $this->divideIntoPages($result);

        $this->view->assignMultiple([
            'term' => $term,
            'settings' => $this->settings,
            'currentPage' => $page,
            'previousPage' => ($page > 1 ? $page - 1 : null),
            'nextPage' => (isset($totalPages[$page+1]) ? $page + 1 : null),
            'totalPages' => $totalPages,
            'result' => $result
        ]);

    }

    /**
     * Builds an array of pages for the pagination
     * This is needed because vanilla Fluid is too stupid to run a simple for(count)...
     *
     * @param  array $result
     * @param  int $currentPage
     * @return array
     */
    protected function divideIntoPages($result) {

        $pageCount = (int)round($result['hits']['total'] / $this->settings['search']['resultsPerPage']);

        $totalPages = [];

        for ($i = 1; $i <= $pageCount; $i++) {
            $totalPages[$i] = $i;
        }

        return $totalPages;
    }

}

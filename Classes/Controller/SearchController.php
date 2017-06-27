<?php
namespace PAGEmachine\Searchable\Controller;

use PAGEmachine\Searchable\Query\SearchQuery;
use PAGEmachine\Searchable\Search;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

class SearchController extends ActionController {

    /**
     * Search Query
     *
     * @var \PAGEmachine\Searchable\Query\SearchQuery
     * @inject
     */
    protected $searchQuery;

    public function initializeObject()
    {
        $this->searchQuery->setDefaultSettings($this->settings['search']);
    }

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

        if ($term) {
            $this->searchQuery
                ->setFeatureSettings($this->settings['features'])
                ->setTerm($term)
                ->setPage($page);

            $result = $this->searchQuery->execute();
        }

        $pagesArray = array_fill(1, $this->searchQuery->getPageCount(), null);

        $this->view->assignMultiple([
            'term' => $term,
            'settings' => $this->settings,
            'currentPage' => $page,
            'previousPage' => ($page > 1 ? $page - 1 : null),
            'nextPage' => (array_key_exists($page+1, $pagesArray) ? $page + 1 : null),
            'totalPages' => $pagesArray,
            'result' => $result
        ]);

    }

}

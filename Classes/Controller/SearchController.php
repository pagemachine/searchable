<?php
namespace PAGEmachine\Searchable\Controller;

use Psr\Http\Message\ResponseInterface;
use PAGEmachine\Searchable\Query\SearchQuery;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

class SearchController extends ActionController
{
    /**
     * @var SearchQuery $searchQuery
     */
    protected $searchQuery;

    /**
     * @param SearchQuery $searchQuery
     */
    public function injectSearchQuery(SearchQuery $searchQuery): void
    {
        $this->searchQuery = $searchQuery;
    }

    public function initializeObject()
    {
        $this->searchQuery->init();
        $this->searchQuery->setDefaultSettings($this->settings['search']);
    }

    /**
     * Renders the search form
     *
     * @param string $term
     * @return void
     */
    public function searchbarAction($term = null): ResponseInterface
    {
        $this->view->assign("settings", $this->settings);
        $this->view->assign("term", $term);
        return $this->htmlResponse();
    }

    /**
     * Renders the search form for a live search
     *
     * @return void
     */
    public function liveSearchbarAction(): ResponseInterface
    {
        $this->view->assign("settings", $this->settings);
        return $this->htmlResponse();
    }

    /**
     * Performs a search and returns the result
     *
     * @param string $term
     * @param int $page
     * @return void
     */
    public function resultsAction($term = null, $page = 1): ResponseInterface
    {
        $result = [];

        if ($term) {
            $this->searchQuery
                ->setTerm($term)
                ->setPage($page)
                ->setPluginMode(true)
                ->setFeatureSettings($this->settings['features']);

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
            'result' => $result,
        ]);
        return $this->htmlResponse();
    }
}

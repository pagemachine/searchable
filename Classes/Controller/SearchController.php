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
     * @return void
     */
    public function resultsAction($term = null) {

        if ($term) {
            $result = Search::getInstance()->search($term);
        }

        $this->view->assign('result', $result);
        $this->view->assign('term', $term);

    }


}

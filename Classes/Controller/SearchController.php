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
     * @return void
     */
    public function searchAction() {

    }

    /**
     * Performs a search and returns the result
     * 
     * @param string $term
     * @return void
     */
    public function resultAction($term) {

        $result = Search::getInstance()->search($term);

        $this->view->assign('result', $result);
        $this->view->assign('term', $term);

    }


}

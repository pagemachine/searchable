<?php
namespace PAGEmachine\Searchable\Controller;

use Elasticsearch\ClientBuilder;
use PAGEmachine\Searchable\Indexer\PagesIndexer;
use PAGEmachine\Searchable\Search;
use PAGEmachine\Searchable\Service\ExtconfService;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

class BackendController extends ActionController {

    /**
     * Backend Template Container
     *
     * @var string
     */
    protected $defaultViewObjectName = BackendTemplateView::class;

    /**
     * Backend controller overview action to show general information about the elasticsearch instance
     * 
     * @return void
     */
    public function startAction() {

         try {

            $client = ClientBuilder::create()->build();

            $index = ExtconfService::getIndex();

            $this->view->assign("health", $client->cluster()->health());
            $this->view->assign("index", $client->indices()->stats(['index' => $index])['indices'][$index]);
            
        } catch (\Exception $e) {

            $this->addFlashMessage($e->getMessage(), get_class($e), AbstractMessage::ERROR); 
        }       




    }

    /**
     * Testing action to index pages
     * 
     * @return void
     */
    public function indexPagesAction() {

        $indexer = new PagesIndexer(ExtconfService::getIndex());

        $response = $indexer->run();

        if ($response['errors']) {
            $this->addFlashMessage("Something went wrong with your request.", "Error", AbstractMessage::ERROR);
        } else {
            $this->addFlashMessage("Request took " . $response['took'] . "ms.", "Success");
        }

        
        $this->redirect("start");

    }

    /**
     * Function to run search tests in the backend.
     * @todo remove this when everything works or extend to a debuggig device
     * @param string $term
     * @return void
     */
    public function searchAction($term) {

        $result = Search::getInstance()->search($term);

        $this->view->assign('result', $result);
        $this->view->assign('term', $term);


    }


}

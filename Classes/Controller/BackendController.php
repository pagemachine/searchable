<?php
namespace PAGEmachine\Searchable\Controller;

use Elasticsearch\ClientBuilder;
use PAGEmachine\Searchable\IndexManager;
use PAGEmachine\Searchable\Indexer\PagesIndexer;
use PAGEmachine\Searchable\Search;
use PAGEmachine\Searchable\Service\ExtconfService;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Http\HttpRequest;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Object\ObjectManager;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

class BackendController extends ActionController {

    /**
     * @var \PAGEmachine\Searchable\Indexer\IndexerFactory
     * @inject
     */
    protected $indexerFactory;

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

    /**
     * Runs a http request directly to elasticsearch (debug)
     *
     * @param  string $url
     * @param  string $body
     * @return string $answer
     */
    public function requestAction($url = '', $body = '') {

        if ($url != '') {
            $request = GeneralUtility::makeInstance(HttpRequest::class, $url);

            if ($body != '') {

                $request->setBody($body);
            }
            $result = $request->send();


            $this->view->assign("response", 
                print_r(
                    json_decode(
                        $result->getBody(),
                        true
                    ),
                    true
                )
            );        
        } else {
            $url = "http://localhost:9200/typo3/";
        }

        $this->view->assign("url", $url);
        $this->view->assign("body", $body);



    }

}

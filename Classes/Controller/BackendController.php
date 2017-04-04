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

    /**
     * Resets all indices (basically truncates elasticsearch database and recreates all mappings)
     *
     * @return void
     */
    public function resetIndicesAction() {

        $indexers = $this->indexerFactory->makeIndexers();

        $mapping = [];

        foreach ($indexers as $indexer) {

            $mapping[$indexer->getType()] = $indexer->getMapping();
        }


        $indexManager = IndexManager::getInstance();

        foreach (ExtconfService::getIndices() as $index) {

            $indexManager->resetIndex($index, $mapping);
        }

        $this->addFlashMessage("Index reset complete.");
        $this->redirect("start");

    }

    /**
     * 
     * @param int $language
     * @return void
     */
    public function indexFullAction($language = 0) {

        $indexers = $this->indexerFactory->makeIndexers($language);

        if (!empty($indexers)) {

            foreach ($indexers as $indexer) {

                foreach ($indexer->run() as $resultMessage) {

                    $this->addFlashMessage($resultMessage);
                }
                
            }

            IndexManager::getInstance()->resetUpdateIndex();
            $this->addFlashMessage("Indexing finished.");

        } else {

            $this->addFlashMessage("No indexers found for language " . $language . ". Doing nothing.", "", AbstractMessage::WARNING);
        }

        $this->redirect("start");

    }

    /**
     * 
     * @return void
     */
    public function indexPartialAction() {

        foreach (ExtconfService::getIndices() as $language => $name) {

            $indexers = $this->indexerFactory->makeIndexers($language);

             if (!empty($indexers)) {

                foreach ($indexers as $indexer) {

                    $result = $indexer->runUpdate();

                    if ($result['errors']) {

                        $this->addFlashMessage("There was an error running " . $indexerConfiguration['indexer'] . ".");

                        \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($result, __METHOD__, 8);
                        die();
                    }
                }

                $this->addFlashMessage("Partial Indexing finished.");

            } else {

                $this->addFlashMessage("No indexers found. Doing nothing.", "", AbstractMessage::WARNING);
            }

        }

        IndexManager::getInstance()->resetUpdateIndex();

        $this->redirect("start");
    }

}

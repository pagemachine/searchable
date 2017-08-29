<?php
namespace PAGEmachine\Searchable\Controller;

use PAGEmachine\Searchable\Connection;
use PAGEmachine\Searchable\IndexManager;
use PAGEmachine\Searchable\Search;
use PAGEmachine\Searchable\Service\ExtconfService;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

class BackendController extends ActionController
{
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
    public function startAction()
    {
        try {
            $this->view->assign("updates", $this->fetchScheduledUpdates());

            $stats = IndexManager::getInstance()->getStats();

            $this->view->assign("health", $stats['health']);
            $this->view->assign("indices", $stats['indices']);
        } catch (\Exception $e) {
            $this->addFlashMessage($e->getMessage(), get_class($e), AbstractMessage::ERROR);
        }
    }

    /**
     * Fetches scheduled updates for backend module
     *
     * @return array
     */
    protected function fetchScheduledUpdates()
    {
        $client = Connection::getClient();

        $updates = $client->search([
            'index' => ExtconfService::getInstance()->getUpdateIndex(),
            'type' => '',
            'body' => [
                'query' => [
                    'match_all' => new \stdClass(),
                ],
            ],
        ]);

        $updates['count'] = $client->count(['index' => ExtconfService::getInstance()->getUpdateIndex()])['count'];

        return $updates;
    }

    /**
     * Function to run search tests in the backend.
     * @todo remove this when everything works or extend to a debuggig device
     * @param string $term
     * @return void
     */
    public function searchAction($term)
    {
        $result = Search::getInstance()->search($term, false);

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
    public function requestAction($url = '', $body = '')
    {
        if ($url != '') {
            $result = $this->request($url, $body);

            $this->view->assign(
                "response",
                print_r(
                    json_decode(
                        $result['body'],
                        true
                    ),
                    true
                )
            );

            switch ($result['status']) {
                case '200':
                    $resultColor = 'success';
                    break;
                case '404':
                    $resultColor = 'danger';
                    break;
                default:
                    $resultColor = 'warning';
                    break;
            }

            $this->view->assignMultiple([
                'status' => $result['status'],
                'color' => $resultColor,
            ]);
        } else {
            $url = "http://localhost:9200/typo3/";
        }

        $this->view->assign("url", $url);
        $this->view->assign("body", $body);
    }

    /**
     * Processes a direct request to ES
     *
     * @param  string $url
     * @param  string $body
     * @return array
     */
    protected function request($url, $body)
    {
        if (\TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version) >= 8001000) {
            return $this->doRequest($url, $body);
        } else {
            return $this->doRequestLegacy($url, $body);
        }
    }

    /**
     * Request processing for TYPO3 >= 8
     *
     * @param  string $url
     * @param  string $body
     * @return array
     */
    protected function doRequest($url, $body)
    {
        $requestFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Http\RequestFactory::class);

        $response = $requestFactory->request(
            $url,
            'GET',
            [
                'body' => $body ?: '',
                'http_errors' => false,
            ]
        );

        return [
            'status' => $response->getStatusCode(),
            'body' => $response->getBody()->getContents(),
        ];
    }

    /**
     * Legacy processing for TYPO3 7
     *
     * TYPO3 7 legacy support
     * @deprecated
     *
     * @param  string $url
     * @param  string $body
     * @return array
     */
    protected function doRequestLegacy($url, $body)
    {
        $request = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Http\HttpRequest::class, $url);

        if ($body != '') {
            $request->setBody($body);
        }
        $result = $request->send();

        return [
            'status' => $result->getStatus(),
            'body' => $result->getBody(),
        ];
    }
}

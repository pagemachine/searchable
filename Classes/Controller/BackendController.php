<?php
namespace PAGEmachine\Searchable\Controller;

use PAGEmachine\Searchable\Connection;
use PAGEmachine\Searchable\Indexer\IndexerFactory;
use PAGEmachine\Searchable\IndexManager;
use PAGEmachine\Searchable\Search;
use PAGEmachine\Searchable\Service\ExtconfService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Messaging\AbstractMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

class BackendController extends ActionController
{
    /**
     * @var IndexerFactory $indexerFactory
     */
    protected $indexerFactory;
    public function __construct(private ModuleTemplateFactory $moduleTemplateFactory)
    {
    }

    public function injectIndexerFactory(IndexerFactory $indexerFactory): void
    {
        $this->indexerFactory = $indexerFactory;
    }

    /**
     * Backend controller overview action to show general information about the elasticsearch instance
     */
    public function startAction(): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        try {
            $this->view->assign("updates", $this->fetchScheduledUpdates());

            $stats = IndexManager::getInstance()->getStats();

            $this->view->assign("health", $stats['health']);
            $this->view->assign("indices", $stats['indices']);
        } catch (\Exception $e) {
            $this->addFlashMessage($e->getMessage(), $e::class, AbstractMessage::ERROR);
        }
        $moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($moduleTemplate->renderContent());
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
     */
    public function searchAction($term): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $result = Search::getInstance()->search($term);

        $this->view->assign('result', $result);
        $this->view->assign('term', $term);
        $moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($moduleTemplate->renderContent());
    }

    /**
     * Runs a http request directly to elasticsearch (debug)
     *
     * @param  string $url
     * @param  string $body
     */
    public function requestAction($url = '', $body = ''): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        if ($url != '') {
            $result = $this->request($url, $body);

            $this->view->assign('response', json_decode($result['body'], true));

            $resultColor = match ($result['status']) {
                '200' => 'success',
                '404' => 'danger',
                default => 'warning',
            };

            $this->view->assignMultiple([
                'status' => $result['status'],
                'color' => $resultColor,
            ]);
        } else {
            $hosts = ExtconfService::getInstance()->getHostsSettings();
            $url = sprintf('%s/typo3/', $hosts[0] ?? 'http://localhost:9200');
        }

        $this->view->assign("url", $url);
        $this->view->assign("body", $body);
        $moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($moduleTemplate->renderContent());
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
        $requestFactory = GeneralUtility::makeInstance(RequestFactory::class);

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
}

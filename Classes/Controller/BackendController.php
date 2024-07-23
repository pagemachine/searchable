<?php
namespace PAGEmachine\Searchable\Controller;

use PAGEmachine\Searchable\Domain\Repository\UpdateRepository;
use PAGEmachine\Searchable\Indexer\IndexerFactory;
use PAGEmachine\Searchable\IndexManager;
use PAGEmachine\Searchable\Query\SearchQuery;
use PAGEmachine\Searchable\Service\ExtconfService;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

/*
 * This file is part of the Pagemachine Searchable project.
 */

class BackendController extends ActionController
{
    protected IndexerFactory $indexerFactory;

    protected UpdateRepository $updateRepository;

    public function injectIndexerFactory(IndexerFactory $indexerFactory): void
    {
        $this->indexerFactory = $indexerFactory;
    }

    public function injectUpdateRepository(UpdateRepository $updateRepository): void
    {
        $this->updateRepository = $updateRepository;
    }

    public function __construct(private readonly ModuleTemplateFactory $moduleTemplateFactory)
    {
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
            $this->addFlashMessage($e->getMessage(), $e::class, ContextualFeedbackSeverity::ERROR);
        }
        $moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($moduleTemplate->renderContent());
    }

    /**
     * Fetches scheduled updates for backend module
     *
     */
    protected function fetchScheduledUpdates()
    {
        return $this->updateRepository->findAll();
    }

    /**
     * Function to run search tests in the backend.
     * @todo remove this when everything works or extend to a debuggig device
     * @param string $term
     */
    public function searchAction($term): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $query = GeneralUtility::makeInstance(SearchQuery::class);
        $query
            ->setTerm($term)
            ->setRespectLanguage(false);

        $result = $query->execute();

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

            $this->view->assign('response', json_decode((string) $result['body'], true));

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
            $indices = ExtconfService::getInstance()->getIndices();
            $url = sprintf('%s/%s/', $this->getDefaultHost(), $indices[0] ?? 'typo3');
        }

        $this->view->assign("url", $url);
        $this->view->assign("body", $body);
        $moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($moduleTemplate->renderContent());
    }

    /**
     * Shows the analyze form and results
     */
    public function analyzeAction(string $text = '', string $selectedIndex = '', string $hostUrl = ''): ResponseInterface
    {
        $moduleTemplate = $this->moduleTemplateFactory->create($this->request);
        $indices = ExtconfService::getInstance()->getIndices();

        if (empty($selectedIndex) || !in_array($selectedIndex, $indices)) {
            $selectedIndex = reset($indices);
        }

        if (empty($hostUrl)) {
            $hostUrl = $this->getDefaultHost();
        }

        if (!empty($text)) {
            try {
                $url = sprintf('%s/%s/_analyze', $hostUrl, $selectedIndex);
                $body = json_encode(['text' => $text]);
                $result = $this->request($url, $body);
                $this->view->assign('response', json_decode((string) $result['body'], true));
            } catch (\Exception $e) {
                $this->addFlashMessage($e->getMessage(), 'Error analyzing text', ContextualFeedbackSeverity::ERROR);
            }
        }

        $this->view->assignMultiple([
            'text' => $text,
            'indices' => array_combine($indices, $indices),
            'selectedIndex' => $selectedIndex,
            'hostUrl' => $hostUrl,
        ]);

        $moduleTemplate->setContent($this->view->render());
        return $this->htmlResponse($moduleTemplate->renderContent());
    }

    /**
     * Gets the default elasticsearch host URL
     */
    private function getDefaultHost(): string
    {
        $hosts = ExtconfService::getInstance()->getHostsSettings();
        return $hosts[0] ?? 'http://localhost:9200';
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
                'headers' => [
                    'content-type' => 'application/json',
                ],
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

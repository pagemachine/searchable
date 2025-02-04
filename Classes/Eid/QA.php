<?php

declare(strict_types=1);

namespace PAGEmachine\Searchable\Eid;

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\ResponseFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class QA extends AbstractEidHandler
{
    private RequestFactory $requestFactory;
    private string $apiUrl = '';
    private string $authToken = '';

    public function __construct()
    {
        $settings = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['searchable'] ?? [];

        $this->apiUrl = $settings['api']['searchUrl'] ?? null;
        $this->authToken = $settings['qa_token']['authtoken'] ?? null;

        $this->requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
    }

    /**
     * Returns results for given term
     *
     * @param   string $question
     * @param   array $data
     * @return  string $response
     * @return  int $index
     */
    public function processRequest(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $rawInput = file_get_contents('php://input');
        $postParams = json_decode($rawInput, true) ?? [];

        $question = $postParams["question"];
        $reqdata = $postParams["data"];
        
        if($reqdata && $question && $this->apiUrl && $this->authToken)
        {
            $data = [
                "input" => [
                    "question" => $question,
                    "data" => json_encode($reqdata)
                ]
            ];

            try {
                $responseData = $this->request($this->apiUrl, 'POST', [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'Authorization' => 'Bearer ' . $this->authToken,
                    ],
                    'json' => $data,
                ]);
                $responseData = $responseData["output"];
            } catch (\Exception $e) {
                $responseData = ['error' => $e->getMessage()];
            }
        }
        else{
            $responseData = ['error' => "missing parameter"];
        }
        header('Content-Type: application/json');
        echo json_encode($responseData);
        exit;
    }

    private function request(string $url, string $method, array $options = []): array
    {
        $options['http_errors'] = false;
        $response = $this->requestFactory->request($url, $method, $options);

        if ($response->getStatusCode() === 200) {
            $json = json_decode($response->getBody()->getContents(), true);
            return $json ?? [];
        }

        throw new \Exception('API request failed (code ' . $response->getStatusCode() . ')');
    }
}

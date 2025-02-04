<?php
declare(strict_types=1);

namespace PAGEmachine\Searchable\Eid;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class QA extends AbstractEidHandler
{
    private readonly RequestFactory $requestFactory;
    private string $apiUrl = '';
    private string $authToken = '';
    private string $apiKey = '';
    private string $prompt = '';

    public function __construct()
    {
        $settings = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['searchable'] ?? [];

        $this->apiUrl = $settings['api']['searchUrl'] ?? "";
        $this->authToken = $settings['authtoken']['authtokentext'] ?? "";
        $this->apiKey = $settings['apiKey']['apiKeytext'] ?? "";
        $this->prompt = $settings['prompt']['prompttext'] ?? "";

        $this->requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
    }

    /**
     * Returns results for given term
     *
     * @param   ServerRequestInterface $request The request object
     * @return  ResponseInterface The response object
     */
    public function processRequest(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $rawInput = file_get_contents('php://input');
        $postParams = json_decode($rawInput, true) ?? [];

        $question = $postParams["question"];
        $reqdata = $postParams["data"];
        $prompt = $this->prompt;

        $headers = [
            'accept'=> 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => $this->authToken,
            'apikey'=> $this->apiKey,
        ];

        $json = [
            "question" => $question,
            "prompt" => $prompt,
            "data" => $reqdata,
        ];

        $responseData = $this->request($this->apiUrl, "POST", ['headers' => $headers, 'json' => $json]);

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

            if (is_array($json)) {
                return $json;
            }

            if (isset($json)) {
                return ['text' => $json];
            }
        }

        return [];
    }
}

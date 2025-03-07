<?php
declare(strict_types=1);

namespace PAGEmachine\Searchable\Eid;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class QA extends AbstractEidHandler
{
    private readonly RequestFactory $requestFactory;
    private string $apiUrl = '';
    private string $authToken = '';
    private string $apiKey = '';
    private string $supportPrompt = '';

    public function __construct()
    {
        $settings = $GLOBALS['TYPO3_CONF_VARS']['EXTENSIONS']['searchable'] ?? [];

        $this->apiUrl = $settings['aigude']['searchUrl'] ?? '';
        $this->authToken = $settings['aigude']['authToken'] ?? '';
        $this->apiKey = $settings['aigude']['apiKey'] ?? '';
        $this->supportPrompt = $settings['qasupportPrompt']['supportPrompt'] ?? '';

        $this->requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
    }

    /**
     * Processes the incoming request and returns a JSON response.
     *
     * @param ServerRequestInterface $request The request object
     * @return ResponseInterface The response object
     */
    public function processRequest(ServerRequestInterface $request): ResponseInterface
    {
        $queryParams = $request->getQueryParams();
        $rawInput = file_get_contents('php://input');
        $postParams = json_decode($rawInput, true) ?? [];

        $question = $postParams['question'] ?? '';
        $reqdata = $postParams['data'] ?? [];
        $reqlang = $postParams['lang'] ?? '';
        $prompt = $this->supportPrompt;

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            'Authorization' => $this->authToken,
            'apikey' => $this->apiKey,
        ];

        $json = [
            'question' => $question,
            'prompt' => $prompt,
            'data' => $reqdata,
            'lang' => $reqlang,
        ];

        $responseData = $this->request($this->apiUrl, 'POST', ['headers' => $headers, 'json' => $json]);

        return new JsonResponse($responseData);
    }

    /**
     * Sends an HTTP request to the AI service and returns the response.
     *
     * @param string $url The request URL
     * @param string $method The HTTP method
     * @param array $options The request options
     * @return array The decoded JSON response
     */
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

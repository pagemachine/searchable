<?php
declare(strict_types = 1);

namespace PAGEmachine\Searchable\LinkBuilder\Frontend;

use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class FrontendRequest implements FrontendRequestInterface
{
    /**
     * @var RequestFactory
     */
    private $requestFactory;

    public function __construct()
    {
        $this->requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
    }

    public function send(UriInterface $baseUri, array $linkConfigurations): array
    {
        $response = $this->requestFactory->request(
            (string)$baseUri->withPath('/-/searchable/urls'),
            'POST',
            [
                'form_params' => [
                    'configurations' => $linkConfigurations,
                ],
                'http_errors' => false,
            ]
        );
        // \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump((string)$response->getBody(), __METHOD__, 8, defined('TYPO3_cliMode') || defined('TYPO3_REQUESTTYPE') && (TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_CLI));
        $uris = json_decode((string)$response->getBody(), true);

        return $uris;
    }
}

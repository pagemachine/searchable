<?php
declare(strict_types = 1);

namespace PAGEmachine\Searchable\LinkBuilder\Frontend;

use Psr\Http\Message\UriInterface;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class LegacyFrontendRequest implements FrontendRequestInterface
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
            (string)$baseUri,
            'POST',
            [
                'query' => [
                    'eID' => 'searchable_linkbuilder',
                ],
                'form_params' => [
                    'configuration' => $linkConfigurations,
                ],
                'http_errors' => false,
            ]
        );
        $uris = json_decode($response->getBody()->getContents(), true);

        return $uris;
    }
}

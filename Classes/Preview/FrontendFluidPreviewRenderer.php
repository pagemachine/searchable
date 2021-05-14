<?php
declare(strict_types = 1);

namespace PAGEmachine\Searchable\Preview;

use PAGEmachine\Searchable\Service\ExtconfService;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

final class FrontendFluidPreviewRenderer extends AbstractPreviewRenderer implements PreviewRendererInterface
{
    /**
     * @var RequestFactory
     */
    private $requestFactory;

    /**
     * @var array
     */
    protected $config = [
        'templateName' => 'Preview/Default',
    ];

    public function __construct()
    {
        $this->requestFactory = GeneralUtility::makeInstance(RequestFactory::class);
    }

    /**
     * Renders the preview
     *
     * @param array $record
     * @return string
     */
    public function render($record)
    {
        $baseUri = new Uri(ExtconfService::getInstance()->getFrontendDomain());

        $response = $this->requestFactory->request(
            (string)$baseUri->withPath('/-/searchable/fluid-previews'),
            'POST',
            [
                'form_params' => [
                    'record' => $record,
                    'config' => $this->config,
                ],
                'http_errors' => false,
            ]
        );

        return (string)$response->getBody();
    }
}

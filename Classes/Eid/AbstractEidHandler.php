<?php
namespace PAGEmachine\Searchable\Eid;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Abstract eID handler
 */
abstract class AbstractEidHandler
{
    /**
     * Additional options for this handler
     *
     * @var array
     */
    protected $options = [];

    /**
     * Processes a autosuggest request (< TYPO3 V8)
     *
     * @return void
     */
    public function processRequestLegacy()
    {
        $term = GeneralUtility::_GET('term');
        $this->options = GeneralUtility::_GET('options');

        $result = $this->getResults($term);

        header('Content-type: application/json');
        echo json_encode($result);
    }

    /**
     * Process request
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @return null|ResponseInterface
     */
    public function processRequest(ServerRequestInterface $request, ResponseInterface $response)
    {
        $term = $request->getQueryParams()['term'];
        $this->options = $request->getQueryParams()['options'];

        $result = $this->getResults($term);

        $response = $response->withHeader('Content-type', 'application/json');
        $response->getBody()->write(json_encode($result));
        return $response;
    }

    /**
     * Returns results for given term - placeholder function
     *
     * @param  string $term
     * @return array $result
     */
    protected function getResults($term)
    {
        return [];
    }
}

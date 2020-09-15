<?php
namespace PAGEmachine\Searchable\Eid;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\Response;

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
     * Process request
     *
     * @param ServerRequestInterface $request
     */
    public function processRequest(ServerRequestInterface $request): ResponseInterface
    {
        $term = $request->getQueryParams()['term'];
        $this->options = $request->getQueryParams()['options'];

        $result = $this->getResults($term);

        $response = (new Response())->withHeader('Content-Type', 'application/json; charset=utf-8');
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

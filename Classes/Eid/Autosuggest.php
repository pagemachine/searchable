<?php
namespace PAGEmachine\Searchable\Eid;

use PAGEmachine\Searchable\Query\AutosuggestQuery;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Eid-based autosuggest class
 */
class Autosuggest {

    /**
     * Processes a autosuggest request (< TYPO3 V8)
     *
     * @return void
     */
    public function processRequestLegacy()
    {
        $term = $_GET['term'];

        $query = GeneralUtility::makeInstance(AutosuggestQuery::class);

        $query
            ->setTerm($term);

        $result = $query->execute();

        $suggestions = [];

        if (!empty($result['suggest']['searchable_autosuggest'][0]['options'])) {

            foreach ($result['suggest']['searchable_autosuggest'][0]['options'] as $suggestion) {
                $suggestions[] = $suggestion['text'];
            }
        }

        header('Content-type: application/json');
        echo json_encode(['suggestions' => $suggestions]);
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

        $query = GeneralUtility::makeInstance(AutosuggestQuery::class);

        $query
            ->setTerm($term);

        $result = $query->execute();

        $suggestions = [];

        if (!empty($result['suggest']['searchable_autosuggest'][0]['options'])) {

            foreach ($result['suggest']['searchable_autosuggest'][0]['options'] as $suggestion) {
                $suggestions[] = $suggestion['text'];
            }
        }

        $response = $response->withHeader('Content-type', 'application/json');
        $response->getBody()->write(json_encode(['suggestions' => $suggestions]));
        return $response;
    }

}

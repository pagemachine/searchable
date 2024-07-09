<?php
namespace PAGEmachine\Searchable\Query;

use PAGEmachine\Searchable\LanguageIdTrait;
use PAGEmachine\Searchable\Service\ExtconfService;
use TYPO3\CMS\Core\Core\Environment;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Query class for searching
 */
class SearchQuery extends AbstractQuery
{
    use LanguageIdTrait;

    /**
     * The array that is filled and later sent to the elasticsearch client for bulk indexing
     *
     * @var array $parameters
     */
    protected $parameters = [
        'body' => [],
        'index' => '',
    ];

    /**
     * @param string $key
     * @return mixed
     */
    public function getBodyParameter($key)
    {
        return isset($this->parameters['body'][$key]) ? $this->parameters[$key] : null;
    }

    /**
     * @param string $key
     * @return SearchQuery
     */
    public function setBodyParameter($key, mixed $parameter)
    {
        $this->parameters['body'][$key] = $parameter;

        return $this;
    }


    /**
     * Whether to limit the query to a specific language index or not
     * @var bool $respectLanguage
     */
    protected $respectLanguage = true;

    /**
     * @return bool
     */
    public function getRespectLanguage()
    {
        return $this->respectLanguage;
    }

    /**
     * @param bool $respectLanguage
     * @return SearchQuery
     */
    public function setRespectLanguage($respectLanguage)
    {
        $this->respectLanguage = $respectLanguage;

        return $this;
    }

    /**
     * @var int $language
     */
    protected $language = null;

    /**
     * @return int
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * @param int $language
     * @return SearchQuery
     */
    public function setLanguage($language)
    {
        $this->language = $language;

        return $this;
    }

    /**
     * Offset the query. Used for pagination
     * @var int $from
     */
    protected $from = 0;

    /**
     * @return int
     */
    public function getFrom()
    {
        return $this->from;
    }

    /**
     * @param int $from
     * @return SearchQuery
     */
    public function setFrom($from)
    {
        $this->from = $from;

        return $this;
    }


    /**
     * @var int $size
     */
    protected $size = 10;

    /**
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @param int $size
     * @return SearchQuery
     */
    public function setSize($size)
    {
        $this->size = $size;

        return $this;
    }

    /**
     * Sets the page offset
     *
     * @param int $page
     * @return SearchQuery
     */
    public function setPage($page)
    {
        $this->from = (int)($page - 1) * $this->size;

        return $this;
    }


    /**
     * @var string $searchType
     */
    protected $searchType = "multi_match";

    /**
     * @return string
     */
    public function getSearchType()
    {
        return $this->searchType;
    }

    /**
     * @param string $searchType
     * @return SearchQuery
     */
    public function setSearchType($searchType)
    {
        $this->searchType = $searchType;

        return $this;
    }


    /**
     * @var array $searchFields
     */
    protected $searchFields = ["*"];

    /**
     * @return array
     */
    public function getSearchFields()
    {
        return $this->searchFields;
    }

    /**
     * @param array $searchFields
     * @return SearchQuery
     */
    public function setSearchFields($searchFields)
    {
        $this->searchFields = $searchFields;

        return $this;
    }


    /**
     * @var string $term
     */
    protected $term = "";

    /**
     * @return string
     */
    public function getTerm()
    {
        return $this->term;
    }

    /**
     * @param string $term
     * @return SearchQuery
     */
    public function setTerm($term)
    {
        $this->term = $term;

        return $this;
    }

    /**
     * @var array $result
     */
    protected $result;

    /**
     * @return array
     */
    public function getResult()
    {
        return $this->result;
    }


    /**
     * Execute method, should be overriden with the concrete command to the client
     * and return the response
     *
     * @return array $response
     */
    public function execute()
    {
        $this->build();

        // Prevent searching over all existing indices if no index is set
        if (empty($this->parameters['index'])) {
            $this->logger->error("No index set for search query");
            return [];
        }

        try {
            $response = $this->client->search($this->getParameters());

            if (!empty($response['errors'])) {
                $this->logger->error("Search Query response contains errors: ", $response);
            }

            $this->result = $response;
        } catch (\Exception $e) {
            $this->logger->error("Elasticsearch-PHP encountered an error while searching: " . $e->getMessage());

            $applicationContext = Environment::getContext();
            if ($applicationContext->isDevelopment()) {
                throw $e;
            }

            $response = [];
        }

        return $response;
    }

    /**
     *
     * @param array $settings
     */
    public function setDefaultSettings($settings = [])
    {
        $this->setSize($settings['resultsPerPage']);
    }

    /**
     * Returns the page count based on the current result
     *
     * @return int
     */
    public function getPageCount()
    {
        if (!empty($this->result)) {
            return (int)ceil($this->result['hits']['total']['value'] / $this->size);
        }

        return 0;
    }

    /**
     * Builds the query
     *
     * @return void
     */
    protected function build()
    {
        $this->parameters['body'] = [
            'query' => [
                $this->searchType => [
                    'fields' => $this->searchFields,
                    'query' => $this->term,
                ],
            ],
            'from' => $this->from,
            'size' => $this->size,
        ];

        $this->applyIndex();
        $this->applyFeatures();
    }

    protected function applyIndex()
    {
        if ($this->respectLanguage === true) {
            $language = $this->language ?: $this->getLanguageId();

            $indices = ExtconfService::getIndicesByLanguage($language);
        } else {
            $indices = ExtconfService::getIndices();
        }

        $this->parameters['index'] = implode(',', $indices);
    }
}

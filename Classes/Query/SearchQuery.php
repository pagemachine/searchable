<?php
namespace PAGEmachine\Searchable\Query;

use PAGEmachine\Searchable\Service\ExtconfService;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Query class for searching
 */
class SearchQuery extends AbstractQuery {

    /**
     * The array that is filled and later sent to the elasticsearch client for bulk indexing
     * 
     * @var array $parameters
     */
    protected $parameters = [
        'body' => []
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
     * @param mixed $parameter
     * @return void
     */
    public function setBodyParameter($key, $parameter)
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
     * @return void
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
     * @return void
     */
    public function setLanguage($language)
    {
        $this->language = $language;
        return $this;
    }


    /**
     * @var bool $highlighting
     */
    protected $highlighting = false;
    
    /**
     * @return bool
     */
    public function getHighlighting()
    {
        return $this->highlighting;
    }
    
    /**
     * @param bool $highlighting
     * @return void
     */
    public function setHighlighting($highlighting)
    {
        $this->highlighting = $highlighting;
        return $this;
    }


    /**
     * @var bool $suggest
     */
    protected $suggest;
    
    /**
     * @return bool
     */
    public function getSuggest()
    {
        return $this->suggest;
    }
    
    /**
     * @param bool $suggest
     * @return void
     */
    public function setSuggest($suggest)
    {
        $this->suggest = $suggest;
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
     * @return void
     */
    public function setFrom($from)
    {
        $this->from = $from;
        return $this;
    }


    /**
     * @var int $size
     */
    protected $size;
    
    /**
     * @return int
     */
    public function getSize()
    {
        return $this->size;
    }
    
    /**
     * @param int $size
     * @return void
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
     */
    public function setPage($page)
    {
        $this->from = (int)($page - 1) * $this->size;
    }


    /**
     * @var string $searchType
     */
    protected $searchType = "match";
    
    /**
     * @return string
     */
    public function getSearchType()
    {
        return $this->searchType;
    }
    
    /**
     * @param string $searchType
     * @return void
     */
    public function setSearchType($searchType)
    {
        $this->searchType = $searchType;
        return $this;
    }


    /**
     * @var string $searchFields
     */
    protected $searchFields = "_all";
    
    /**
     * @return string
     */
    public function getSearchFields()
    {
        return $this->searchFields;
    }
    
    /**
     * @param string $searchFields
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

        try {
          $response = $this->client->search($this->getParameters());

          if ($response['errors']) {

              $this->logger->error("Search Query response contains errors: ", $response);
          }

          $this->result = $response;
          return $response;         
        } 
        catch (\Exception $e) {

            $this->logger->error("Elasticsearch-PHP encountered an error while searching: " . $e->getMessage());
            return [];
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
    public function getPageCount() {

        if (!empty($this->result)) {

            return (int)ceil($this->result['hits']['total'] / $this->size);
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
                    $this->searchFields => $this->term
                ]
            ],
            'from' => $this->from,
            'size' => $this->size
        ];

        if ($this->respectLanguage === true) {

            $language = $this->language ?: $GLOBALS['TSFE']->sys_language_uid;

            $this->parameters['index'] = ExtconfService::hasIndex($language) ? ExtconfService::getIndex($language) : ExtconfService::getIndex();
        }

        if ($this->highlighting) {

            $this->parameters['body']['highlight'] = [
                'pre_tags' => ["<span class='searchable_highlight'>"],
                'post_tags' => ["</span>"],
                'fields' => [
                    '_all' => new \stdClass()
                ]
            ];
        }

        if ($this->suggest) {

            $this->parameters['body']['suggest'] = [
                'suggestion' => [
                    'text' => $this->term,
                    'term' => [
                        'field' => '_all'
                    ]
                ]
            ];
        }
    }

}

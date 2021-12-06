<?php
namespace PAGEmachine\Searchable;

use Elasticsearch\Client;
use PAGEmachine\Searchable\Connection;
use PAGEmachine\Searchable\LanguageIdTrait;
use PAGEmachine\Searchable\Service\ExtconfService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * The main class for searching
 */
class Search implements SingletonInterface
{
    use LanguageIdTrait;

    /**
     * Elasticsearch client
     * @var Client
     */
    protected $client;

    /**
     * @param Client|null $client
     */
    public function __construct(Client $client = null)
    {
        $this->client = $client ?: Connection::getClient();
    }

    /**
     * @return Search
     */
    public static function getInstance()
    {
        return GeneralUtility::makeInstance(Search::class);
    }

    /**
     * Search everything (all indices and types) for the term
     * @param  string $term
     * @param  array $options
     * @param  bool $respectLanguage If set, the search will be limited to the current FE language (if there is an index for it) or the default language
     * @param  int $forceLanguage Forces the given language id
     * @return array
     */
    public function search($term, $options = [], $respectLanguage = true, $forceLanguage = null)
    {
        $params = [
            'body' => [
                'query' => [
                    'multi_match' => [
                        'query' => $term,
                    ],
                ],
                //Only load meta fields, not the whole source
                 '_source' => [
                     'searchable_meta',
                 ],
            ],
        ];

        if (!empty($options)) {
            foreach ($options as $key => $option) {
                $params['body'][$key] = $option;
            }
        }

        if ($respectLanguage === true) {
            $language = $forceLanguage ?: $this->getLanguageId();

            $indicies = ExtconfService::getLanguageIndicies($language);
            if (!empty($indicies)) {
                foreach($indicies as $index){
                    $params['index'] +=  (string) $index +',';
                }
            } else{
                $params['index'] =  ExtconfService::getIndex();
            }

            //$params['index'] == ExtconfService::hasIndex($language) ? ExtconfService::getIndex($language) : ExtconfService::getIndex();
         }


        $result = $this->client->search($params);
        return $result;
    }

    /**
     * Special search query used by DataCollectors to retrieve updated records
     * @param  string $table
     * @return array
     */
    public function searchUpdates($table)
    {
        $params = [
            'index' => ExtconfService::getInstance()->getUpdateIndex(),
            'type' => 'updates',
            'body' => [
                'query' => [
                    'match' => [
                        'table' => $table,
                    ],
                ],
            ],
        ];
        $result = $this->client->search($params);


        $updates = [];

        if ($result['hits']['total'] > 0) {
            foreach ($result['hits']['hits'] as $hit) {
                $updates[] = $hit['_source'];
            }
        }

        return $updates;
    }
}

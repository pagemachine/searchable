<?php
namespace PAGEmachine\Searchable\Feature;

use PAGEmachine\Searchable\Feature\Traits\FieldCollectionTrait;
use PAGEmachine\Searchable\Query\QueryInterface;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * CompletionSuggestFeature
 * Creates mapping, indexing and search parameters for CompletionSuggesters (Autosuggest functionality)
 */
class CompletionSuggestFeature extends AbstractFeature implements FeatureInterface
{
    use FieldCollectionTrait;

    /**
     * @var array
     */
    protected static $defaultConfiguration = [
        //The fields to include into the completion field
        'fields' => [],
        // The field to store the values for completion in
        'completionField' => 'searchable_autosuggest',
        // Which analyzer to use for the suggestion field. Defaults to "simple", which will only find letters.
        // If you want to find f.ex. integers as well, use "standard".
        'analyzer' => 'simple',
        // Prevents the whole source from being loaded for each suggestion. Should always be true if used with AJAX/search-as-you-type
        'limitSource' => true,
        // If set, splits a sentence into different words so each word of the sentence
        'splitIntoWords' => false,
        // Regex to use for splitting. Default is every unicode language letter (\p{L}) and digits (\d)
        'splitRegex' => '/([^\p{L}\d])+/u',
    ];

    /**
     *
     * @var string
     */
    public static $featureName = "completionSuggest";

    /**
     * Entry point to modify mapping
     *
     * @param  array  $mapping
     * @param  array  $configuration
     * @return array  $mapping
     */
    public static function modifyMapping($mapping, $configuration)
    {
        $mapping['properties'][$configuration['completionField']] = [
            'type' => 'completion',
            'analyzer' => $configuration['analyzer'],
        ];

        return $mapping;
    }

    /**
     * Entry point to modify records before insert/update
     *
     * @param  array  $record
     * @return array  $record
     */
    public function modifyRecord($record)
    {
        $content = [];

        if (!empty($this->config['fields'])) {
            $content = $this->collectFields($record, $this->config['fields']);

            if ($this->config['splitIntoWords'] ?? false) {
                $content = $this->splitFields($content);
            }
        }

        $content = $this->collectFieldFromSubRecords($record, $this->config['completionField'], $content, true);

        if (!empty($content)) {
            $record[$this->config['completionField']]['input'] = $content;
        }

        return $record;
    }

    /**
     * Splits fields into tokens for indexing
     *
     * @param  array  $fields
     * @return array
     */
    protected function splitFields($fields = [])
    {
        $splittedContent = [];
        if (!empty($fields)) {
            foreach ($fields as $field) {
                $split = preg_split($this->config['splitRegex'], $field);
                $splittedContent = array_merge($splittedContent, $split);
            }
        }

        return $splittedContent;
    }

    /**
     * Modifies a query before it is executed
     *
     * @param QueryInterface $query
     * @return QueryInterface
     */
    public function modifyQuery(QueryInterface $query)
    {
        $parameters = $query->getParameters();
        $parameters['body']['suggest'][$this->config['completionField']] = [
            'prefix' => $query->getTerm(),
            'completion' => [
                'field' => $this->config['completionField'],
            ],
        ];
        if ($this->config['limitSource']) {
            $parameters['body']['_source'] = $this->config['completionField'];
        }
        $query->setParameters($parameters);

        return $query;
    }
}

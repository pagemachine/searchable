<?php
namespace PAGEmachine\Searchable\Feature;

use PAGEmachine\Searchable\Feature\Traits\FieldCollectionTrait;
/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * CompletionSuggestFeature
 * Creates mapping, indexing and search parameters for CompletionSuggesters (Autosuggest functionality)
 */
class CompletionSuggestFeature extends AbstractFeature implements FeatureInterface {

    use FieldCollectionTrait;

    /**
     * @var array
     */
    protected static $defaultConfiguration = [
        //The fields to include into the completion field
        'fields' => [],
        // The field to store the values for completion in
        'completionField' => 'searchable_autosuggest',
        // Prevents the whole source from being loaded for each suggestion. Should always be true if used with AJAX/search-as-you-type
        'limitSource' => true
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
            'type' => 'completion'
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
        if (!empty($this->config['fields'])) {

            $content = $this->collectFields($record, $this->config['fields']);               
        }
        $content = $this->collectFieldFromSubRecords($record, $this->config['completionField'], $content, true);

        if (!empty($content)) {

            $record[$this->config['completionField']]['input'] = $content;
        }
        return $record;
    }

    /**
     * Modifies a query before it is executed
     *
     * @param array $query
     * @return array
     */
    public function modifyQuery($query)
    {
        $query['body']['suggest'][$this->config['completionField']] = [
            'prefix' => $query['body']['query']['multi_match']['query'],
            'completion' => [
                'field' => $this->config['completionField']
            ]
        ];

        return $query;
    }

}

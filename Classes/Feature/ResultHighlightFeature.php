<?php
namespace PAGEmachine\Searchable\Feature;

use PAGEmachine\Searchable\Feature\Traits\FieldCollectionTrait;
use PAGEmachine\Searchable\Query\QueryInterface;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * ResultHighlightFeature
 * Creates mapping, indexing and search parameters for result highlighting
 */
class ResultHighlightFeature extends AbstractFeature implements FeatureInterface {

    use FieldCollectionTrait;

    /**
     * @var array
     */
    protected static $defaultConfiguration = [
        //The fields to include into the highlighting field
        'fields' => [],
        'highlightField' => 'searchable_highlight'
    ];

    /**
     *
     * @var string
     */
    public static $featureName = "highlighting";

    /**
     * Entry point to modify mapping
     *
     * @param  array  $mapping
     * @param  array  $configuration
     * @return array  $mapping
     */
    public static function modifyMapping($mapping, $configuration)
    {
        $mapping['properties'][$configuration['highlightField']] = [
            'type' => 'text',
            'include_in_all' => false
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

            $highlightContent = $this->collectFields($record, $this->config['fields']);               
        }
        $highlightContent = $this->collectFieldFromSubRecords($record, $this->config['highlightField'], $highlightContent, true);

        if (!empty($highlightContent)) {

            $record[$this->config['highlightField']] = implode(" ", $highlightContent);
        }


        return $record;
    }

    /**
     * Modifies a query before it is executed
     *
     * @param QueryInterface $query
     * @return array
     */
    public function modifyQuery(QueryInterface $query)
    {
        $parameters = $query->getParameters();
        $parameters['body']['query']['multi_match']['fields'][] = $this->config['highlightField'];
        $parameters['body']['highlight'] = [
            'pre_tags' => ["<span class='searchable-highlight'>"],
            'post_tags' => ["</span>"],
            'fields' => [
                $this->config['highlightField'] => new \stdClass()
            ]
        ];
        $query->setParameters($parameters);

        return $query;
    }


}

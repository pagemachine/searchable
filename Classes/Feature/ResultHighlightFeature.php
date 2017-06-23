<?php
namespace PAGEmachine\Searchable\Feature;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * ResultHighlightFeature
 * Creates mapping, indexing and search parameters for result highlighting
 */
class ResultHighlightFeature extends AbstractFeature implements FeatureInterface {

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

            $highlightContent = $this->collectHighlightFields($record, $this->config['fields']);               
        }
        $highlightContent = $this->collectFieldFromSubRecords($record, $this->config['highlightField'], $highlightContent);

        if (!empty($highlightContent)) {

            $record[$this->config['highlightField']] = implode(" ", $highlightContent);
        }


        return $record;
    }

    /**
     * function to collect highlight fields
     *
     * @param array $record
     * @param array $fields
     */
    protected function collectHighlightFields($record, $fields, $highlightContent = []) {
        /**
         * @var array
         */
        $highlightContent = [];

        foreach ($fields as $field) {
            if (array_key_exists($field, $record) && !empty($record[$field])) {
                $highlightContent[] = $record[$field];
            }
        }
        return $highlightContent;
    }

    /**
     * Collects a field from Subrecords
     *
     * @param  array $record
     * @param  string $fieldname
     * @param  array  $collection
     * @return array $collection
     */
    protected function collectFieldFromSubRecords(&$record, $fieldname, $collection = []) {

        foreach ($record as $column => $value) {

            if (is_array($value) && !isset($value['uid'])) {

                foreach ($value as $childKey => $childRecord) {

                    if (!empty($childRecord[$fieldname])) {

                        $collection[] = $childRecord[$fieldname];
                        unset($record[$column][$childKey][$fieldname]);
                    }
                }
            }
            else if (is_array($value)) {

                if (!empty($value[$fieldname])) {

                    $collection[] = $value[$fieldname];
                    unset($record[$column][$fieldname]);
                }                
            }
        }

        return $collection;
    }

    /**
     * Modifies a query before it is executed
     *
     * @param array $query
     * @return array
     */
    public function modifyQuery($query)
    {
        $query['body']['query']['multi_match']['fields'][] = $this->config['highlightField'];
        $query['body']['highlight'] = [
            'pre_tags' => ["<span class='searchable-highlight'>"],
            'post_tags' => ["</span>"],
            'fields' => [
                $this->config['highlightField'] => new \stdClass()
            ]
        ];

        return $query;
    }


}

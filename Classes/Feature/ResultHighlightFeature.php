<?php
namespace PAGEmachine\Searchable\Feature;

use PAGEmachine\Searchable\Feature\Traits\FieldCollectionTrait;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * ResultHighlightFeature
 * Creates mapping, indexing and search parameters for result highlighting
 * Collects all fields and copies them manually to the highlight field
 *
 * @deprecated, will be removed in V3
 * Use HighlightFeature instead
 */
class ResultHighlightFeature extends HighlightFeature implements FeatureInterface
{
    use FieldCollectionTrait;

    /**
     * @var array
     */
    protected static $defaultConfiguration = [
        //The fields to include into the highlighting field
        'fields' => [],
        'highlightField' => 'searchable_highlight',
        'strip_tags' => true,
    ];

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
            'include_in_all' => false,
            'store' => true,
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
            $record[$this->config['highlightField']] = $this->config['stripTags'] ? strip_tags(implode(" ", $highlightContent)) : implode(" ", $highlightContent);
        }


        return $record;
    }
}

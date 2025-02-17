<?php
namespace PAGEmachine\Searchable\Feature;

use PAGEmachine\Searchable\Query\QueryInterface;

/*
 * This file is part of the Pagemachine Searchable project.
 */

/**
 * HighlightFeature
 * Creates mapping and search parameters for result highlighting
 * Uses the ES copy_to mapping option to fill the highlight field
 * See: https://www.elastic.co/guide/en/elasticsearch/reference/current/copy-to.html
 */
class HighlightFeature extends AbstractFeature implements FeatureInterface
{
    /**
     * @var array
     */
    protected static $defaultConfiguration = [
        // The fields to include into the highlighting field
        'fields' => [],
        'highlightField' => 'searchable_highlight',
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
            // Highlight field needs to be stored as copied content so it is not included in _source
            'store' => true,
        ];

        $mapping = self::addRecursiveCopyTo($configuration['fields'], $mapping, $configuration['highlightField']);

        return $mapping;
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
        $parameters['body']['query']['multi_match']['fields'][] = $this->config['highlightField'];
        $parameters['body']['highlight'] = [
            'pre_tags' => ["<span class='searchable-highlight'>"],
            'post_tags' => ["</span>"],
            'fields' => [
                $this->config['highlightField'] => new \stdClass(),
            ],
        ];
        $query->setParameters($parameters);

        return $query;
    }
}

<?php
namespace PAGEmachine\Searchable\Feature;

use PAGEmachine\Searchable\Query\QueryInterface;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * TermSuggestFeature
 * Creates mapping, indexing and search parameters for term suggestions ("did you mean")
 */
class TermSuggestFeature extends AbstractFeature implements FeatureInterface
{
    /**
     * @var array
     */
    protected static $defaultConfiguration = [
        // The fields to include for the term suggestions
        'fields' => [],
    ];

    /**
     *
     * @var string
     */
    public static $featureName = "termSuggest";

    public static $fieldName = "searchable_suggest";

    /**
     * Entry point to modify mapping
     *
     * @param  array  $mapping
     * @param  array  $configuration
     * @return array  $mapping
     */
    public static function modifyMapping($mapping, $configuration)
    {
        $mapping['properties'][self::$fieldName] = [
            'type' => 'text',
            // Suggestion field needs to be stored as copied content so it is not included in _source
            'store' => true,
        ];

        $mapping = self::addRecursiveCopyTo($configuration['fields'], $mapping, self::$fieldName);

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
        $parameters['body']['suggest']['suggestion'] = [
            'text' => $query->getTerm(),
            'term' => [
                'field' => self::$fieldName,
            ],
        ];
        $query->setParameters($parameters);

        return $query;
    }
}

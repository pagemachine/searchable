<?php
namespace PAGEmachine\Searchable\Feature;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * TermSuggestFeature
 * Creates mapping, indexing and search parameters for term suggestions ("did you mean")
 */
class TermSuggestFeature extends AbstractFeature implements FeatureInterface {

    /**
     * @var array
     */
    protected static $defaultConfiguration = [
    ];

    /**
     *
     * @var string
     */
    public static $featureName = "termSuggest";

    /**
     * Modifies a query before it is executed
     *
     * @param array $query
     * @return array
     */
    public function modifyQuery($query)
    {
        $query['body']['suggest'] = [
            'suggestion' => [
                'text' => $query['body']['query']['multi_match']['query'],
                'term' => [
                    'field' => '_all'
                ]
            ]
        ];

        return $query;
    }


}

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
        'field' => 'title',
    ];

    /**
     *
     * @var string
     */
    public static $featureName = "termSuggest";

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
                'field' => $this->config['field'],
            ],
        ];
        $query->setParameters($parameters);

        return $query;
    }
}

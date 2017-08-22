<?php
namespace PAGEmachine\Searchable\Tests\Unit\Feature;

use PAGEmachine\Searchable\Feature\TermSuggestFeature;
use PAGEmachine\Searchable\Query\QueryInterface;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Testcase for TermSuggestFeature
 */
class TermSuggestFeatureTest extends UnitTestCase
{
    /**
     * @var TermSuggestFeature
     */
    protected $feature;

    /**
     * Set up this testcase
     */
    public function setUp()
    {
        $this->feature = new TermSuggestFeature();
    }

    /**
     * @test
     */
    public function addsSuggestionQueryString()
    {
        $query = $this->prophesize(QueryInterface::class);
        $query->getParameters()->willReturn([
            'body' => [
                'query' => [
                    'multi_match' => [
                        'fields' => ['foo', 'bar'],
                        'query' => 'searchword',
                    ],
                ],
            ],
        ]);
        $query->getTerm()->willReturn('searchword');

        $query->setParameters([
            'body' => [
                'query' => [
                    'multi_match' => [
                        'fields' => ['foo', 'bar'],
                        'query' => 'searchword',
                    ],
                ],
                'suggest' => [
                    'suggestion' => [
                        'text' => 'searchword',
                        'term' => [
                            'field' => '_all',
                        ],
                    ],
                ],
            ],
        ])->shouldBeCalled();
        $this->feature->modifyQuery($query->reveal());
    }
}

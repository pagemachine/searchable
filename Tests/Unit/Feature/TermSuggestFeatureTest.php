<?php
namespace PAGEmachine\Searchable\Tests\Unit\Feature;

use PHPUnit\Framework\Attributes\Test;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use PAGEmachine\Searchable\Feature\TermSuggestFeature;
use PAGEmachine\Searchable\Query\QueryInterface;
use Prophecy\PhpUnit\ProphecyTrait;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Testcase for TermSuggestFeature
 */
class TermSuggestFeatureTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * @var TermSuggestFeature
     */
    protected $feature;

    /**
     * Set up this testcase
     */
    public function setUp(): void
    {
        $this->feature = new TermSuggestFeature();
    }

    #[Test]
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

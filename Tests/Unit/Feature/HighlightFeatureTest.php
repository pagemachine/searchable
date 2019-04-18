<?php
namespace PAGEmachine\Searchable\Tests\Unit\Feature;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use PAGEmachine\Searchable\Feature\HighlightFeature;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Testcase for HighlightFeature
 */
class HighlightFeatureTest extends UnitTestCase
{
    /**
     * @var HighlightFeature
     */
    protected $feature;

    /**
     * Set up this testcase
     */
    public function setUp()
    {
        $this->feature = new HighlightFeature();
    }

    /**
     * @test
     */
    public function addsFieldMapping()
    {
        $configuration = [
            'fields' => [
                'fieldone',
            ],
            'highlightField' => 'searchable_highlight',
        ];


        $mapping = HighlightFeature::modifyMapping([], $configuration);

        $expectedMapping = [
            'properties' => [
                'fieldone' => [
                    'copy_to' => 'searchable_highlight',
                ],
            ],
        ];

        $this->assertArraySubset($expectedMapping, $mapping);
    }

    /**
     * @test
     */
    public function keepsExistingMappingPropiertes()
    {
        $configuration = [
            'fields' => [
                'fieldone',
            ],
            'highlightField' => 'searchable_highlight',
        ];

        $mapping = [
            'properties' => [
                'fieldone' => [
                    'type' => 'text',
                ],
            ],
        ];


        $mapping = HighlightFeature::modifyMapping($mapping, $configuration);

        $expectedMapping = [
            'properties' => [
                'fieldone' => [
                    'type' => 'text',
                    'copy_to' => 'searchable_highlight',
                ],
            ],
        ];

        $this->assertArraySubset($expectedMapping, $mapping);
    }

    /**
     * @test
     */
    public function mapsRecursively()
    {
        $configuration = [
            'fields' => [
                'fieldone',
                'sublevel' => [
                    'fieldtwo',
                ],
            ],
            'highlightField' => 'searchable_highlight',
        ];

        $mapping = [
            'properties' => [
                'fieldone' => [
                    'type' => 'text',
                ],
                'sublevel' => [
                    'properties' => [
                        'fieldtwo' => [
                            'type' => 'text',
                        ],
                    ],
                ],
            ],
        ];

        $mapping = HighlightFeature::modifyMapping($mapping, $configuration);

        $expectedMapping = [
            'properties' => [
                'fieldone' => [
                    'type' => 'text',
                    'copy_to' => 'searchable_highlight',
                ],
                'sublevel' => [
                    'properties' => [
                        'fieldtwo' => [
                            'type' => 'text',
                            'copy_to' => 'searchable_highlight',
                        ],
                    ],
                ],
            ],
        ];

        $this->assertArraySubset($expectedMapping, $mapping);
    }
}

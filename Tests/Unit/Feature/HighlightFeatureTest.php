<?php
namespace PAGEmachine\Searchable\Tests\Unit\Feature;

use PAGEmachine\Searchable\Feature\HighlightFeature;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/*
 * This file is part of the Pagemachine Searchable project.
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
    public function setUp(): void
    {
        parent::setUp();

        $this->feature = new HighlightFeature();
    }

    /**
     * @test
     */
    #[Test]
    public function addsFieldMapping()
    {
        $configuration = [
            'fields' => [
                'fieldone',
            ],
            'highlightField' => 'searchable_highlight',
        ];


        $mapping = HighlightFeature::modifyMapping([], $configuration);

        $this->assertEquals(['searchable_highlight'], $mapping['properties']['fieldone']['copy_to'] ?? null);
    }

    /**
     * @test
     */
    #[Test]
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

        $this->assertEquals('text', $mapping['properties']['fieldone']['type'] ?? null);
        $this->assertEquals(['searchable_highlight'], $mapping['properties']['fieldone']['copy_to'] ?? null);
    }

    /**
     * @test
     */
    #[Test]
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

        $this->assertEquals('text', $mapping['properties']['fieldone']['type'] ?? null);
        $this->assertEquals(['searchable_highlight'], $mapping['properties']['fieldone']['copy_to'] ?? null);

        $this->assertEquals('text', $mapping['properties']['sublevel']['properties']['fieldtwo']['type'] ?? null);
        $this->assertEquals(['searchable_highlight'], $mapping['properties']['sublevel']['properties']['fieldtwo']['copy_to'] ?? null);
    }
}

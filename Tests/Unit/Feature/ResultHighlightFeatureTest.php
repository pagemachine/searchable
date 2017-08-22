<?php
namespace PAGEmachine\Searchable\Tests\Unit\Feature;

use PAGEmachine\Searchable\Feature\ResultHighlightFeature;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Testcase for ResultHighlightFeature
 */
class ResultHighlightFeatureTest extends UnitTestCase
{
    /**
     * @var ResultHighlightFeature
     */
    protected $feature;

    /**
     * Set up this testcase
     */
    public function setUp()
    {
        $this->feature = new ResultHighlightFeature();
    }

    /**
     * @test
     */
    public function addsFieldsRecursive()
    {
        $this->feature = new ResultHighlightFeature([
            'fields' => [
                'fieldone',
                'fieldtwo',
            ],
            'highlightField' => 'searchable_highlight',
        ]);

        $record = [
            'fieldone' => 'one',
            'fieldtwo' => 'two',
        ];
        $record = $this->feature->modifyRecord($record);

        $this->assertEquals('one two', $record['searchable_highlight']);
    }

    /**
     * @test
     */
    public function mergesHighlightFieldFromChildRecordsAndRemovesIt()
    {
        $this->feature = new ResultHighlightFeature([
            'highlightField' => 'searchable_highlight',
            'fields' => [
                'fieldone',
            ],
        ]);

        $record = [
            'fieldone' => 'one',
            'children' => [
                0 => [
                    'uid' => 1,
                    'searchable_highlight' => 'two',
                ],

            ],
        ];
        $record = $this->feature->modifyRecord($record);

        $this->assertEquals('one two', $record['searchable_highlight']);
        $this->assertArrayNotHasKey('searchable_highlight', $record['children'][0]);
    }

    /**
     * @test
     */
    public function mergesHighlightFieldFromSingleChildRecord()
    {
        $this->feature = new ResultHighlightFeature([
            'highlightField' => 'searchable_highlight',
            'fields' => [
                'fieldone',
            ],
        ]);

        $record = [
            'fieldone' => 'one',
            'child' => [
                'uid' => 1,
                'searchable_highlight' => 'two',
            ],
        ];
        $record = $this->feature->modifyRecord($record);

        $this->assertEquals('one two', $record['searchable_highlight']);
        $this->assertArrayNotHasKey('searchable_highlight', $record['child']);
    }
}

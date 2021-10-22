<?php
namespace PAGEmachine\Searchable\Tests\Unit\Feature;

use Nimut\TestingFramework\TestCase\UnitTestCase;
use PAGEmachine\Searchable\Feature\CompletionSuggestFeature;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Testcase for CompletionSuggestFeature
 */
class CompletionSuggestFeatureTest extends UnitTestCase
{
    /**
     * @var CompletionSuggestFeature
     */
    protected $feature;

    /**
     * Set up this testcase
     */
    public function setUp(): void
    {
        $this->feature = new CompletionSuggestFeature([
            'fields' => [
                'fieldone',
                'fieldtwo',
                'fieldthree',
            ],
            'completionField' => 'autosuggest',
        ]);
    }

    /**
     * @test
     */
    public function addsFieldsToplevel()
    {
        $record = [
            'fieldone' => 'one',
            'fieldtwo' => 'two',
        ];
        $record = $this->feature->modifyRecord($record);

        $this->assertEquals(['one', 'two'], $record['autosuggest']['input']);
    }

    /**
     * @test
     */
    public function addsFieldsFromChild()
    {
        $record = [
            'fieldone' => 'one',
            'child' => [
                'uid' => 100,
                'autosuggest' => [
                    'two',
                    'three',
                ],
            ],
        ];
        $record = $this->feature->modifyRecord($record);

        $this->assertEquals(['one', 'two', 'three'], $record['autosuggest']['input']);
        $this->assertArrayNotHasKey('autosuggest', $record['child']);
    }

    /**
     * @test
     */
    public function addsFieldsFromChildren()
    {
        $record = [
            'fieldone' => 'one',
            'children' => [
                0 => [
                    'uid' => 100,
                    'autosuggest' => [
                        'two',
                        'three',
                    ],
                ],
            ],
        ];
        $record = $this->feature->modifyRecord($record);

        $this->assertEquals(['one', 'two', 'three'], $record['autosuggest']['input']);
        $this->assertArrayNotHasKey('autosuggest', $record['children'][0]);
    }
}

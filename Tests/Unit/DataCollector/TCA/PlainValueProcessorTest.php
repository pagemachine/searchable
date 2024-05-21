<?php
namespace PAGEmachine\Searchable\Tests\Unit\DataCollector\TCA;

use PAGEmachine\Searchable\DataCollector\TCA\PlainValueProcessor;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Testcase for PlainValueProcessor
 */
class PlainValueProcessorTest extends UnitTestCase
{
    /**
     * @var PlainValueProcessor
     */
    protected $plainValueProcessor;

    /**
     * Set up this testcase
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->plainValueProcessor = new PlainValueProcessor();
    }

    /**
     * @test
     */
    public function convertsCheckboxValues()
    {
        $fieldTca = [
            'type' => 'check',
            'items' => [
                ['foo', ''],
                ['baz', ''],
                ['foobar', ''],
                ['foobarbaz', ''],
            ],
        ];

        $value = 3;

        $expectedOutput = 'foo, baz';

        $output = $this->plainValueProcessor->processCheckboxField($value, $fieldTca);

        $this->assertEquals($expectedOutput, $output);
    }

    /**
     * @test
     */
    public function supportsAssociativeCheckboxItems()
    {
        $fieldTca = [
            'type' => 'check',
            'items' => [
                ['label' => 'foo', 'value' => ''],
                ['label' => 'baz', 'value' => ''],
                ['label' => 'foobar', 'value' => ''],
                ['label' => 'foobarbaz', 'value' => ''],
            ],
        ];

        $value = 3;

        $expectedOutput = 'foo, baz';

        $output = $this->plainValueProcessor->processCheckboxField($value, $fieldTca);

        $this->assertEquals($expectedOutput, $output);
    }

    /**
     * @test
     */
    public function convertsRadioValues()
    {
        $fieldTca = [
            'type' => 'radio',
            'items' => [
                ['foo', 1],
                ['baz', 2],
                ['foobar', 3],
            ],
        ];

        $value = 2;

        $expectedOutput = "baz";

        $output = $this->plainValueProcessor->processRadioField($value, $fieldTca);

        $this->assertEquals($expectedOutput, $output);
    }

    /**
     * @test
     */
    public function supportsAssociativeRadioItems()
    {
        $fieldTca = [
            'type' => 'radio',
            'items' => [
                ['label' => 'foo', 'value' => 1],
                ['label' => 'baz', 'value' => 2],
                ['label' => 'foobar', 'value' => 3],
            ],
        ];

        $value = 2;

        $expectedOutput = "baz";

        $output = $this->plainValueProcessor->processRadioField($value, $fieldTca);

        $this->assertEquals($expectedOutput, $output);
    }

    /**
     * @test
     */
    public function convertsStringRadioValues()
    {
        $fieldTca = [
            'type' => 'radio',
            'items' => [
                ['foo', 'foo'],
                ['bazlabel', 'bazvalue'],
            ],
        ];

        $value = 'bazvalue';

        $expectedOutput = "bazlabel";

        $output = $this->plainValueProcessor->processRadioField($value, $fieldTca);

        $this->assertEquals($expectedOutput, $output);
    }
}

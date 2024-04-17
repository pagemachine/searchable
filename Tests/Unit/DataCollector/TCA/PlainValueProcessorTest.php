<?php
namespace PAGEmachine\Searchable\Tests\Unit\DataCollector\TCA;

use PHPUnit\Framework\Attributes\Test;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use PAGEmachine\Searchable\DataCollector\TCA\PlainValueProcessor;

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
        $this->plainValueProcessor = new PlainValueProcessor();
    }

    #[Test]
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

    #[Test]
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

    #[Test]
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

<?php
namespace PAGEmachine\Searchable\Tests\Unit\DataCollector;

use PAGEmachine\Searchable\DataCollector\TCA\FormDataRecord;
use PAGEmachine\Searchable\DataCollector\TCA\PlainValueProcessor;
use PAGEmachine\Searchable\DataCollector\TcaDataCollector;
use Prophecy\Argument;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Testcase for TcaDataCollector
 */
class TcaDataCollectorTest extends UnitTestCase
{
    /**
     * @var TcaDataCollector
     */
    protected $tcaDataCollector;

    /**
     * @var FormDataRecord
     */
    protected $formDataRecord;

    /**
     * @var PlainValueProcessor
     */
    protected $plainValueProcessor;


    /**
     * Set up this testcase
     */
    protected function setUp()
    {
        $configuration = [
            'table' => 'example_table',
            'excludeFields' => [
                'excludeme',
                'excludemetoo'
            ]

        ];

        $this->tcaDataCollector = new TcaDataCollector($configuration);

        $this->formDataRecord = $this->prophesize(FormDataRecord::class);
        $this->plainValueProcessor = $this->prophesize(PlainValueProcessor::class);

        GeneralUtility::setSingletonInstance(FormDataRecord::class, $this->formDataRecord->reveal());
        GeneralUtility::setSingletonInstance(PlainValueProcessor::class, $this->plainValueProcessor->reveal());
    }

    /**
     * @test
     */
    public function processesFlatRecord()
    {
        $recordTca = [
            'columns' => [
                'title' => [
                    'config' => [
                        'type' => 'input'
                    ]
                ],
                'checkboxfield' => [
                    'config' => [
                        'type' => 'check'
                    ]
                ],
                'radiofield' => [
                    'config' => [
                        'type' => 'radio'
                    ]
                ],
                'excludeme' => [
                    'config' => [
                        'type' => 'input'
                    ]
                ],
                'unusedrelation' => [
                    'config' => [
                        'type' => 'select'
                    ]
                ],
                'emptyfield' => [
                    'config' => [
                        'type' => 'input'
                    ]
                ]
            ]
        ];

        $record = [
            'databaseRow' => [
                'uid' => 123,
                'title' => 'foobar',
                'checkboxfield' => '2',
                'radiofield' => '3',
                'excludeme' => 'lalala',
                'emptyfield' => '',
                'unusedrelation' => [
                    1 => 1
                ]
            ],
            'processedTca' => $recordTca
        ];

        $this->formDataRecord->getRecord(123, 'example_table')->willReturn($record);

        $this->plainValueProcessor->processCheckboxField("2", Argument::type("array"))->shouldBeCalled()->willReturn("checkboxvalue");
        $this->plainValueProcessor->processRadioField("3", Argument::type("array"))->shouldBeCalled()->willReturn("radiovalue");


        $expectedOutput = [
            'uid' => 123,
            'title' => 'foobar',
            'checkboxfield' => 'checkboxvalue',
            'radiofield' => 'radiovalue'
        ];

        $this->assertEquals($expectedOutput, $this->tcaDataCollector->getRecord(123));

        
    }


}


<?php
namespace PAGEmachine\Searchable\Tests\Unit\DataCollector;

use PAGEmachine\Searchable\DataCollector\RelationResolver\FormEngine\SelectRelationResolver;
use PAGEmachine\Searchable\DataCollector\RelationResolver\ResolverManager;
use PAGEmachine\Searchable\DataCollector\TCA\FormDataRecord;
use PAGEmachine\Searchable\DataCollector\TCA\PlainValueProcessor;
use PAGEmachine\Searchable\DataCollector\TcaDataCollector;
use Prophecy\Argument;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Object\ObjectManager;

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

        $GLOBALS['TCA']['example_table'] = $recordTca;

        $record = [
            'databaseRow' => [
                'uid' => 123,
                'title' => 'foobar',
                'checkboxfield' => '2',
                'radiofield' => '3',
                'emptyfield' => '',
            ],
            'processedTca' => $recordTca
        ];

        $configuration = [
            'table' => 'example_table',
            'excludeFields' => [
                'excludeme',
                'excludemetoo'
            ]

        ];

        $this->tcaDataCollector = new TcaDataCollector($configuration);

        $this->formDataRecord->getRecord(123, 'example_table', [
            'uid',
            'pid',
            'title',
            'checkboxfield',
            'radiofield',
            'emptyfield'
        ])->shouldBeCalled()->willReturn($record);

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

    /**
     * @test
     */
    public function processesRelations()
    {
        $objectManager = $this->prophesize(ObjectManager::class);
        $subCollector = $this->prophesize(TcaDataCollector::class);
        $subCollector->getConfig()->willReturn(
            ['field' => 'selectfield']
        );

        $objectManager->get(TcaDataCollector::class, Argument::type("array"), 0)->willReturn($subCollector->reveal());

        $configuration = [
            'table' => 'example_table',
            'subCollectors' => [
                'es_selectfield' => [
                    'className' => TcaDataCollector::class,
                    'config' => [
                        'field' => 'selectfield'
                    ]
                ]
            ]

        ];

        $recordTca = [
            'columns' => [
                'selectfield' => [
                    'config' => [
                        'foreign_table' => 'selecttable'
                    ]
                ]
            ]
        ];

        $GLOBALS['TCA']['example_table'] = $recordTca;

        $record = [
            'databaseRow' => [
                'selectfield' => [
                    1 => 2
                ]
            ],
            'processedTca' => $recordTca
        ];

        $this->tcaDataCollector = new TcaDataCollector($configuration);

        $this->formDataRecord->getRecord(123, 'example_table', Argument::type('array'))->willReturn($record);

        $resolver = $this->prophesize(SelectRelationResolver::class);
        $resolver->resolveRelation("selectfield", $record['databaseRow'], $subCollector, $this->tcaDataCollector)->willReturn([[
            'uid' => 123,
            'title' => 'foobar'
        ]]);

        $resolverManager = $this->prophesize(ResolverManager::class);
        $resolverManager->findResolverForRelation("selectfield", $subCollector, $this->tcaDataCollector)->willReturn($resolver->reveal());
        $this->inject($this->tcaDataCollector, "resolverManager", $resolverManager->reveal());

        
        $this->inject($this->tcaDataCollector, "objectManager", $objectManager->reveal());

        $this->tcaDataCollector->addSubCollector("es_selectfield", $subCollector->reveal());

        $expectedOutput = [
            'es_selectfield' => [
                [
                    'uid' => 123,
                    'title' => 'foobar'
                ]
            ]
        ];

        $this->assertEquals($expectedOutput, $this->tcaDataCollector->getRecord(123));
        
    }


}


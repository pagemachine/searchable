<?php
namespace PAGEmachine\Searchable\Tests\Unit\DataCollector;

use PAGEmachine\Searchable\DataCollector\RelationResolver\FormEngine\SelectRelationResolver;
use PAGEmachine\Searchable\DataCollector\RelationResolver\ResolverManager;
use PAGEmachine\Searchable\DataCollector\TCA\FormDataRecord;
use PAGEmachine\Searchable\DataCollector\TCA\PlainValueProcessor;
use PAGEmachine\Searchable\DataCollector\TcaDataCollector;
use PAGEmachine\Searchable\DataCollector\Utility\OverlayUtility;
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
     * @var OverlayUtility
     */
    protected $overlayUtility;

    /**
     * @var FormDataRecord
     */
    protected $formDataRecord;

    /**
     * @var PlainValueProcessor
     */
    protected $plainValueProcessor;

    /**
     * @var ObjectManager
     */
    protected $objectManager;

    /**
     * Set up this testcase
     */
    protected function setUp()
    {

        $this->formDataRecord = $this->prophesize(FormDataRecord::class);
        $this->plainValueProcessor = $this->prophesize(PlainValueProcessor::class);

        $this->overlayUtility = $this->prophesize(OverlayUtility::class);

        $this->tcaDataCollector = new TcaDataCollector([]);

        $this->objectManager = $this->prophesize(ObjectManager::class);
        $this->objectManager->get(Argument::any())->willReturn(null);

        GeneralUtility::setSingletonInstance(FormDataRecord::class, $this->formDataRecord->reveal());
        GeneralUtility::setSingletonInstance(PlainValueProcessor::class, $this->plainValueProcessor->reveal());
        GeneralUtility::setSingletonInstance(OverlayUtility::class, $this->overlayUtility->reveal());
    }

    /**
     * @test
     */
    public function processesFlatRecord()
    {
        $recordTca = [
            'ctrl' => [
                'languageField' => 'languageField'
            ],
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
                'pid' => 567,
                'languageField' => 0,
                'title' => 'foobar',
                'checkboxfield' => '2',
                'radiofield' => '3',
                'emptyfield' => '',
            ],
            'processedTca' => $recordTca
        ];

        $configuration = [
            'table' => 'example_table',
            'sysLanguageOverlay' => 1,
            'mode' => 'blacklist',
            'fields' => [
                'excludeme',
                'excludemetoo',
                'unusedrelation'
            ]

        ];

        $this->tcaDataCollector->__construct($configuration, 0, $this->objectManager->reveal());

        $this->formDataRecord->getRecord(123, 'example_table', [
            'uid',
            'pid',
            'languageField',
            'title',
            'checkboxfield',
            'radiofield',
            'emptyfield'
        ])->shouldBeCalled()->willReturn($record);

        $this->overlayUtility->languageOverlay('example_table', $record['databaseRow'], 0, Argument::type("array"), 1)
            ->shouldBeCalled()
            ->willReturn($record['databaseRow']);

        $this->plainValueProcessor->processCheckboxField("2", Argument::type("array"))->shouldBeCalled()->willReturn("checkboxvalue");
        $this->plainValueProcessor->processRadioField("3", Argument::type("array"))->shouldBeCalled()->willReturn("radiovalue");


        $expectedOutput = [
            'uid' => 123,
            'pid' => 567,
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
        $subCollector = $this->prophesize(TcaDataCollector::class);
        $subCollector->getConfig()->willReturn(
            ['field' => 'selectfield']
        );

        $this->objectManager->get(TcaDataCollector::class, Argument::type("array"), 0)->willReturn($subCollector->reveal());

        $configuration = [
            'table' => 'example_table',
            'sysLanguageOverlay' => 1,
            'mode' => 'whitelist',
            'fields' => ['selectfield'],
            'subCollectors' => [
                'es_selectfield' => [
                    'className' => TcaDataCollector::class,
                    'config' => [
                        'field' => 'selectfield',
                        'sysLanguageOverlay' => 1,
                    ]
                ]
            ]

        ];

        $recordTca = [
            'ctrl' => [
                'languageField' => 'languageField'
            ],
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
                'uid' => 1,
                'pid' => 2,
                'selectfield' => [
                    1 => 2
                ]
            ],
            'processedTca' => $recordTca
        ];

        $this->tcaDataCollector->__construct($configuration, 0, $this->objectManager->reveal());

        $this->formDataRecord->getRecord(123, 'example_table', Argument::type('array'))->willReturn($record);

        $this->overlayUtility->languageOverlay('example_table', $record['databaseRow'], 0, Argument::type("array"), 1)
            ->shouldBeCalled()
            ->willReturn($record['databaseRow']);

        $resolver = $this->prophesize(SelectRelationResolver::class);
        $resolver->resolveRelation("selectfield", $record['databaseRow'], $subCollector, $this->tcaDataCollector)->willReturn([[
            'uid' => 123,
            'title' => 'foobar'
        ]]);

        $resolverManager = $this->prophesize(ResolverManager::class);
        $resolverManager->findResolverForRelation("selectfield", $subCollector, $this->tcaDataCollector)->willReturn($resolver->reveal());
        $this->inject($this->tcaDataCollector, "resolverManager", $resolverManager->reveal());

        $this->tcaDataCollector->addSubCollector("es_selectfield", $subCollector->reveal());

        $expectedOutput = [
            'uid' => 1,
            'pid' => 2,
            'es_selectfield' => [
                [
                    'uid' => 123,
                    'title' => 'foobar'
                ]
            ]
        ];

        $this->assertEquals($expectedOutput, $this->tcaDataCollector->getRecord(123));
        
    }

    /**
     * @test
     */
    public function processesTranslations() {

        $configuration = [
            'table' => 'example_table',
            'sysLanguageOverlay' => 1,
            'mode' => 'whitelist',
            'fields' => ['title']
        ];

        $recordTca = [
            'columns' => [
                'title' => [
                    'config' => [
                        'type' => 'input'
                    ]
                ]
            ]
        ];
        
        $GLOBALS['TCA']['example_table'] = $recordTca;

        $baseRow = [
            'uid' => 1,
            'pid' => 2,
            'title' => 'englishtitle'
        ];

        $translatedRow = [
            'uid' => 1,
            'pid' => 2,
            'title' => 'germantitle'
        ];

        $record = [
            'databaseRow' => $baseRow,
            'processedTca' => $recordTca
        ];

        $this->tcaDataCollector->__construct($configuration, 1);

        $this->formDataRecord->getRecord(1, 'example_table', Argument::type('array'))->willReturn($record);

        $this->overlayUtility->languageOverlay('example_table', $baseRow, 1, Argument::type("array"), 1)->shouldBeCalled()->willReturn($translatedRow);

        $this->assertEquals($translatedRow, $this->tcaDataCollector->getRecord(1));

    }


}


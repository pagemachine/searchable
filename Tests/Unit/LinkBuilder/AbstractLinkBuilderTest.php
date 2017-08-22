<?php
namespace PAGEmachine\Searchable\Tests\Unit\LinkBuilder;

use PAGEmachine\Searchable\LinkBuilder\AbstractLinkBuilder;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Testcase for AbstractLinkBuilder
 */
class AbstractLinkBuilderTest extends UnitTestCase
{
    /**
     * @var AbstractLinkBuilder
     */
    protected $linkBuilder;

    protected function setUp()
    {
        $this->linkBuilder = $this->getMockForAbstractClass(AbstractLinkBuilder::class);
    }

    /**
     * @test
     */
    public function createsFixedLinkConfiguration()
    {

        $record = [];

        $configuration = [
            'titleField' => 'footitle',
            'fixedParts' => [
                'someUid' => 2,
                'additionalParams' => ['foo' => 'bar'],
            ],
            'dynamicParts' => [
            ],
        ];

        $expectedLinkConfiguration = [
            'someUid' => 2,
            'additionalParams' => ['foo' => 'bar'],
        ];

        $this->linkBuilder = $this->getAccessibleMockForAbstractClass(AbstractLinkBuilder::class, ['config' => $configuration]);
        $linkConfiguration = $this->linkBuilder->createLinkConfiguration($record);

        $this->assertEquals($expectedLinkConfiguration, $linkConfiguration);
    }

    /**
     * @test
     */
    public function replacesDynamicFields()
    {

        $configuration = [
            'fixedParts' => [],
            'dynamicParts' => [
                'pageUid' => 'page',
            ],
        ];

        $record = [
            'page' => '123',
        ];

        $this->linkBuilder = $this->getAccessibleMockForAbstractClass(AbstractLinkBuilder::class, ['config' => $configuration]);
        $linkConfiguration = $this->linkBuilder->createLinkConfiguration($record);

        $this->assertEquals([
            'pageUid' => '123',
        ], $linkConfiguration);
    }


    /**
     * @test
     */
    public function replacesNestedDynamicFields()
    {

        $configuration = [
            'fixedParts' => [],
        ];

        $configuration['dynamicParts'] = [
            'pageUid' => 'page',
            'additionalParams' => [
                'param1' => 'property1',
                'param2' => 'property2',
            ],
        ];

        $record = [
            'page' => '123',
            'property1' => 'value1',
            'property2' => 'value2',
        ];

        $this->linkBuilder = $this->getAccessibleMockForAbstractClass(AbstractLinkBuilder::class, ['config' => $configuration]);
        $linkConfiguration = $this->linkBuilder->createLinkConfiguration($record);

        $this->assertArraySubset(['pageUid' => '123'], $linkConfiguration);
        $this->assertArraySubset(['additionalParams' => ['param1' => 'value1', 'param2' => 'value2']], $linkConfiguration);
    }

    /**
     * @test
     */
    public function unsetsEmptyDynamicFieldsAndUsesFixedPartInstead()
    {

        $configuration = [
            'fixedParts' => [],
        ];

        $configuration['fixedParts']['pageUid'] = '123';
        $configuration['dynamicParts']['pageUid'] = 'page';

        $record = [];

        $this->linkBuilder = $this->getAccessibleMockForAbstractClass(AbstractLinkBuilder::class, ['config' => $configuration]);
        $linkConfiguration = $this->linkBuilder->createLinkConfiguration($record);

        $this->assertArraySubset(['pageUid' => '123'], $linkConfiguration);
    }
}

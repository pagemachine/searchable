<?php
namespace PAGEmachine\Searchable\Tests\Unit\LinkBuilder;

use PAGEmachine\Searchable\LinkBuilder\AbstractLinkBuilder;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

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

    protected function setUp(): void
    {
        parent::setUp();

        $this->linkBuilder = $this->getMockForAbstractClass(AbstractLinkBuilder::class);
    }

    /**
     * @test
     * @dataProvider languagesAndLinkConfigurations
     *
     * @param int $language
     * @param array $expectedLinkConfiguration
     */
    public function createsFixedLinkConfigurationWithLanguage($language, array $expectedLinkConfiguration)
    {
        $record = [];

        $configuration = [
            'titleField' => 'footitle',
            'languageParam' => 'LANG',
            'fixedParts' => [
                'someUid' => 2,
                'additionalParams' => ['foo' => 'bar'],
            ],
            'dynamicParts' => [
            ],
        ];

        $this->linkBuilder = $this->getAccessibleMockForAbstractClass(AbstractLinkBuilder::class, ['config' => $configuration]);
        $linkConfiguration = $this->linkBuilder->createLinkConfiguration($record, $language);

        $this->assertEquals($expectedLinkConfiguration, $linkConfiguration);
    }

    /**
     * @return array
     */
    public function languagesAndLinkConfigurations()
    {
        return [
            'default language' => [
                0,
                [
                    'someUid' => 2,
                    'additionalParams' => [
                        'foo' => 'bar',
                    ],
                ],
            ],
            'translation language' => [
                1,
                [
                    'someUid' => 2,
                    'additionalParams' => [
                        'foo' => 'bar',
                        'LANG' => 1,
                    ],
                ],
            ],
        ];
    }

    /**
     * @test
     */
    public function replacesDynamicFields()
    {
        $configuration = [
            'languageParam' => 'L',
            'fixedParts' => [],
            'dynamicParts' => [
                'pageUid' => 'page',
            ],
        ];

        $record = [
            'page' => '123',
        ];

        $this->linkBuilder = $this->getAccessibleMockForAbstractClass(AbstractLinkBuilder::class, ['config' => $configuration]);
        $linkConfiguration = $this->linkBuilder->createLinkConfiguration($record, 0);
        $expectedLinkConfiguration = [
            'pageUid' => '123',
        ];

        $this->assertEquals($expectedLinkConfiguration, $linkConfiguration);
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
        $linkConfiguration = $this->linkBuilder->createLinkConfiguration($record, 0);

        $this->assertSame('123', $linkConfiguration['pageUid'] ?? null);
        $this->assertSame(['param1' => 'value1', 'param2' => 'value2'], $linkConfiguration['additionalParams'] ?? null);
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
        $linkConfiguration = $this->linkBuilder->createLinkConfiguration($record, 0);

        $this->assertSame('123', $linkConfiguration['pageUid'] ?? null);
    }
}

<?php
namespace PAGEmachine\Searchable\Tests\Unit\LinkBuilder;

use PAGEmachine\Searchable\LinkBuilder\AbstractEidLinkBuilder;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Testcase for AbstractEidLinkBuilder
 */
class AbstractEidLinkBuilderTest extends UnitTestCase
{
    /**
     * @var AbstractEidLinkBuilder
     */
    protected $linkBuilder;

    protected function setUp()
    {
        $this->linkBuilder = $this->getMockForAbstractClass(AbstractEidLinkBuilder::class);
    }

    /**
     * @test
     */
    public function convertsToTypoLinkConfig()
    {
        $record = ['title' => 'sometitle'];

        $builderConfig = [
            'titleField' => 'title',
        ];

        $linkConfig = [
            'foo' => 'bar',
        ];

        $typolinkConfig = [
            'title' => 'sometitle',
            'conf' => ['foo' => 'bar'],
        ];

        $this->linkBuilder = $this->getAccessibleMockForAbstractClass(AbstractEidLinkBuilder::class, ['config' => $builderConfig]);
        $linkConfiguration = $this->linkBuilder->convertToTypoLinkConfig($linkConfig, $record);

        $this->assertEquals($typolinkConfig, $linkConfiguration);
    }
}

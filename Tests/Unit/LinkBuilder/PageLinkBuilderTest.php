<?php
namespace PAGEmachine\Searchable\Tests\Unit\LinkBuilder;

use PAGEmachine\Searchable\LinkBuilder\PageLinkBuilder;
use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Testcase for PageLinkBuilder
 */
class PageLinkBuilderTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * @var PageLinkBuilder
     */
    protected $pageLinkBuilder;

    /**
     * Set up this testcase
     */
    public function setUp(): void
    {
        parent::setUp();

        $requestFactoryProphecy = $this->prophesize(RequestFactory::class);
        GeneralUtility::addInstance(RequestFactory::class, $requestFactoryProphecy->reveal());
    }

    /**
     * @test
     */
    public function convertsFromViewHelperConfigToTypoLinkConfig()
    {
        $this->pageLinkBuilder = new PageLinkBuilder();

        $config = [
            'pageUid' => 123,
            'additionalParams' => [
                'foo' => 'bar',
            ],
            'pageType' => 456,
            'noCache' => true,
            'useCacheHash' => false,
            'section' => 'xyz',
            'linkAccessRestrictedPages' => true,
            'absolute' => true,
            'addQueryString' => true,
            'argumentsToBeExcludedFromQueryString' => [
                'someArgument',
            ],
            'addQueryStringMethod' => 'GET',
        ];

        $expectedTypolinkConfig = [
            'parameter' => '123,456',
            'additionalParams' => '&foo=bar',
            'no_cache' => 1,
            'section' => 'xyz',
            'linkAccessRestrictedPages' => 1,
            'forceAbsoluteUrl' => 1,
            'addQueryString' => 1,
            'addQueryString.' => [
                'exclude' => 'someArgument',
                'method' => 'GET',
            ],
        ];

        $typolinkConfig = $this->pageLinkBuilder->finalizeTypoLinkConfig($config, []);

        $this->assertEquals($expectedTypolinkConfig, $typolinkConfig);
    }
}

<?php
namespace PAGEmachine\Searchable\Tests\Unit\LinkBuilder;

use PAGEmachine\Searchable\LinkBuilder\PageLinkBuilder;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Testcase for PageLinkBuilder
 */
class PageLinkBuilderTest extends UnitTestCase {

    /**
     * @var PageLinkBuilder
     */
    protected $pageLinkBuilder;

    /**
     * Set up this testcase
     */
    public function setUp() {

        $this->pageLinkBuilder = new PageLinkBuilder();
    }

    /**
     * @test
     */
    public function createsFixedLinkConfiguration() {

        $record = [];

        $configuration = [
            'titleField' => 'footitle',
            'fixedParts' => [
                'pageUid' => 2,
                'additionalParams' => ['foo' => 'bar'],
                'pageType' => 123,
                'noCache' => true,
                'noCacheHash' => true,
                'section' => 'asection',
                'linkAccessRestrictedPages' => true,
                'absolute' => true,
                'addQueryString' => true,
                'argumentsToBeExcludedFromQueryString' => ['dev', 'null'],
                'addQueryStringMethod' => 'someMethod'
            ],
            'dynamicParts' => [
            ]
        ];

        $expectedLinkConfiguration = [
            'pageUid' => 2,
            'additionalParams' => ['foo' => 'bar'],
            'pageType' => 123,
            'noCache' => true,
            'noCacheHash' => true,
            'section' => 'asection',
            'linkAccessRestrictedPages' => true,
            'absolute' => true,
            'addQueryString' => true,
            'argumentsToBeExcludedFromQueryString' => ['dev', 'null'],
            'addQueryStringMethod' => 'someMethod',
            'title' => 'Link'
        ];

        $this->pageLinkBuilder = new PageLinkBuilder($configuration);

        $linkConfiguration = $this->pageLinkBuilder->createLinkConfiguration($record);

        $this->assertEquals($expectedLinkConfiguration, $linkConfiguration);
        
    }

    /**
     * @test
     */
    public function createsDynamicLinkTitle() {

        $configuration = [
            'titleField' => 'foobar'
        ];

        $record = [
            'foobar' => 'baz'
        ];

        $this->pageLinkBuilder = new PageLinkBuilder($configuration);
        $linkConfiguration = $this->pageLinkBuilder->createLinkConfiguration($record);

        $this->assertArraySubset(['title' => 'baz'], $linkConfiguration);

    }

    /**
     * @test
     */
    public function replacesDynamicFields() {

        $configuration = [
            'dynamicParts' => [
                'pageUid' => 'page'
            ]
        ];

        $record = [
            'page' => '123'
        ];

        $this->pageLinkBuilder = new PageLinkBuilder($configuration);
        $linkConfiguration = $this->pageLinkBuilder->createLinkConfiguration($record);

        $this->assertArraySubset(['pageUid' => '123'], $linkConfiguration);

    }

    /**
     * @test
     */
    public function replacesNestedDynamicFields() {

        $configuration = [
            'dynamicParts' => [
                'pageUid' => 'page',
                'additionalParams' => [
                    'param1' => 'property1',
                    'param2' => 'property2'
                ]
            ]
        ];

        $record = [
            'page' => '123',
            'property1' => 'value1',
            'property2' => 'value2'
        ];

        $this->pageLinkBuilder = new PageLinkBuilder($configuration);
        $linkConfiguration = $this->pageLinkBuilder->createLinkConfiguration($record);

        $this->assertArraySubset(['pageUid' => '123'], $linkConfiguration);
        $this->assertArraySubset(['additionalParams' => ['param1' => 'value1', 'param2' => 'value2']], $linkConfiguration);

    }

    /**
     * @test
     */
    public function unsetsEmptyDynamicFieldsAndUsesFixedPartInstead() {

        $configuration = [
            'fixedParts' => [
                'pageUid' => '123'
            ],
            'dynamicParts' => [
                'pageUid' => 'page'
            ]
        ];

        $record = [];

        $this->pageLinkBuilder = new PageLinkBuilder($configuration);
        $linkConfiguration = $this->pageLinkBuilder->createLinkConfiguration($record);

        $this->assertArraySubset(['pageUid' => '123'], $linkConfiguration);

        
    }



}

<?php
namespace PAGEmachine\Searchable\Tests\Unit\LinkBuilder;

use PAGEmachine\Searchable\LinkBuilder\FileLinkBuilder;
use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Testcase for FileLinkBuilder
 */
class FileLinkBuilderTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * Set up this testcase
     */
    public function setUp(): void
    {
        $requestFactoryProphecy = $this->prophesize(RequestFactory::class);
        GeneralUtility::addInstance(RequestFactory::class, $requestFactoryProphecy->reveal());
    }

    /**
     * @test
     */
    public function createsFileLinkForToplevelFileRecord()
    {
        $config = [
            'titleField' => 'title',
            'fixedParts' => [],
            'fileRecordField' => null,
        ];

        $record = [
            'uid' => 22,
            'somethingelse' => [
                'uid' => 25,
            ],
        ];

        $linkBuilder = new FileLinkBuilder($config);

        $linkConfiguration = $linkBuilder->finalizeTypoLinkConfig([], $record);

        $this->assertEquals('t3://file?uid=22', $linkConfiguration['parameter']);
    }

    /**
     * @test
     */
    public function createsFileLinkForSingleSublevelFile()
    {
        $config = [
            'titleField' => 'title',
            'fixedParts' => [],
            'fileRecordField' => 'file',
        ];

        $record = [
            'uid' => 22,
            'file' => [
                'uid' => 25,
            ],
        ];

        $linkBuilder = new FileLinkBuilder($config);

        $linkConfiguration = $linkBuilder->finalizeTypoLinkConfig([], $record);

        $this->assertEquals('t3://file?uid=25', $linkConfiguration['parameter']);
    }

    /**
     * @test
     */
    public function createsFileLinkForNestedSublevelFile()
    {
        $config = [
            'titleField' => 'title',
            'fixedParts' => [],
            'fileRecordField' => 'file',
        ];

        $record = [
            'uid' => 22,
            'file' => [
                0 => [
                    'uid' => 25,
                ],
            ],
        ];

        $linkBuilder = new FileLinkBuilder($config);

        $linkConfiguration = $linkBuilder->finalizeTypoLinkConfig([], $record);

        $this->assertEquals('t3://file?uid=25', $linkConfiguration['parameter']);
    }
}

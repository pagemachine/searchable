<?php
namespace PAGEmachine\Searchable\Tests\Unit\LinkBuilder;

use PAGEmachine\Searchable\LinkBuilder\FileLinkBuilder;
use PHPUnit\Framework\Attributes\Test;
use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/*
 * This file is part of the Pagemachine Searchable project.
 */

/**
 * Testcase for FileLinkBuilder
 */
class FileLinkBuilderTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * @test
     */
    #[Test]
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
    #[Test]
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
    #[Test]
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

<?php
namespace PAGEmachine\Searchable\Tests\Unit\LinkBuilder;

use PHPUnit\Framework\Attributes\Test;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use PAGEmachine\Searchable\LinkBuilder\FileLinkBuilder;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Testcase for FileLinkBuilder
 */
class FileLinkBuilderTest extends UnitTestCase
{
    /**
     * @var FileLinkBuilder
     */
    protected $linkBuilder;

    /**
     * Set up this testcase
     */
    public function setUp(): void
    {
        $this->linkBuilder = new FileLinkBuilder();
    }

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

        $this->linkBuilder = new FileLinkBuilder($config);

        $linkConfiguration = $this->linkBuilder->finalizeTypoLinkConfig([], $record);

        $this->assertEquals('t3://file?uid=22', $linkConfiguration['parameter']);
    }

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

        $this->linkBuilder = new FileLinkBuilder($config);

        $linkConfiguration = $this->linkBuilder->finalizeTypoLinkConfig([], $record);

        $this->assertEquals('t3://file?uid=25', $linkConfiguration['parameter']);
    }

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

        $this->linkBuilder = new FileLinkBuilder($config);

        $linkConfiguration = $this->linkBuilder->finalizeTypoLinkConfig([], $record);

        $this->assertEquals('t3://file?uid=25', $linkConfiguration['parameter']);
    }
}

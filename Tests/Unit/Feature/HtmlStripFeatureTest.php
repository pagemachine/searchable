<?php
namespace PAGEmachine\Searchable\Tests\Unit\Feature;

/*
 * This file is part of the Pagemachine TÜV Hessen project.
 */

use PAGEmachine\Searchable\Feature\HtmlStripFeature;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for Pagemachine\TuevHessenSite\Searchable\Feature\HtmlStripFeature
 */
class HtmlStripFeatureTest extends UnitTestCase
{
    /**
     * @var HtmlStripFeature
     */
    protected $htmlStripFeature;

    /**
     * Set up this testcase
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->htmlStripFeature = new HtmlStripFeature();
    }

    /**
     * Tear down this testcase
     */
    protected function tearDown(): void
    {
        GeneralUtility::purgeInstances();
    }

    /**
     * @test
     */
    #[Test]
    public function stripsHtmlTags()
    {
        $record = [
            'title' => 'Test title',
            'richtext' => '<p><b>Important</b> text with <abbr title="Hyper Text Markup Language">HTML</abbr> tags.</p>',
            'nested' => [
                'richtext' => '<ul><li>First</li><li>Second</li></ul>',
            ],
        ];
        $expected = [
            'title' => 'Test title',
            'richtext' => 'Important text with HTML tags.',
            'nested' => [
                'richtext' => 'FirstSecond',
            ],
        ];
        $result = $this->htmlStripFeature->modifyRecord($record);

        $this->assertEquals($expected, $result);
    }
}

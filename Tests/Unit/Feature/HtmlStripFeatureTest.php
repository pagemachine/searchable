<?php
namespace PAGEmachine\Searchable\Tests\Unit\Feature;

/*
 * This file is part of the Pagemachine TÜV Hessen project.
 */

use Nimut\TestingFramework\TestCase\UnitTestCase;
use PAGEmachine\Searchable\Feature\HtmlStripFeature;
use TYPO3\CMS\Core\Utility\GeneralUtility;

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
    protected function setUp()
    {
        $this->htmlStripFeature = new HtmlStripFeature();
    }

    /**
     * Tear down this testcase
     */
    protected function tearDown()
    {
        GeneralUtility::purgeInstances();
    }

    /**
     * @test
     */
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

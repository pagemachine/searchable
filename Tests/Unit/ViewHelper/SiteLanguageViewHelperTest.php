<?php
namespace PAGEmachine\Searchable\Tests\Unit\ViewHelper;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

use PAGEmachine\Searchable\ViewHelpers\SiteLanguageViewHelper;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Testcase for SiteLanguageViewHelper
 */
class SiteLanguageViewHelperTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @var SiteLanguageViewHelper
     */
    protected $viewHelper;

    /**
     * Set up this testcase
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->viewHelper = new SiteLanguageViewHelper();
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['TSFE']);
    }

    /**
     * @test
     */
    public function returnsCurrentLanguage()
    {
        $GLOBALS['TSFE'] = $this->prophesize(TypoScriptFrontendController::class)->reveal();

        GeneralUtility::makeInstance(Context::class)->setAspect('language', new LanguageAspect(1));

        $this->assertEquals(1, $this->viewHelper->render());
    }

    /**
     * @test
     */
    public function returnsZeroIfTsfeDoesNotExist()
    {
        $this->assertEquals(0, $this->viewHelper->render());
    }
}

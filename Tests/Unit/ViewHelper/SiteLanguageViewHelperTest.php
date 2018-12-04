<?php
namespace PAGEmachine\Searchable\Tests\Unit\ViewHelper;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

use PAGEmachine\Searchable\ViewHelpers\SiteLanguageViewHelper;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Testcase for SiteLanguageViewHelper
 */
class SiteLanguageViewHelperTest extends TestCase
{
    /**
     * @var SiteLanguageViewHelper
     */
    protected $viewHelper;

    /**
     * Set up this testcase
     */
    protected function setUp()
    {
        $this->viewHelper = $this->getMockBuilder(SiteLanguageViewHelper::class)
            ->setMethods([
                    'getTypoScriptFrontendController',
                    'getLanguageAspect',
                ])
            ->getMock();
    }

    /**
     * @test
     */
    public function returnsCurrentLanguage()
    {
        $tsfe = $this->prophesize(TypoScriptFrontendController::class);

        if (\TYPO3\CMS\Core\Utility\VersionNumberUtility::convertVersionNumberToInteger(TYPO3_version) < 9000000) {
            $tsfe->sys_language_uid = 1;
        } else {
            $languageAspect = $this->prophesize(LanguageAspect::class);
            $languageAspect->getId()->willReturn(1);

            $this->viewHelper->method("getLanguageAspect")->will($this->returnValue($languageAspect->reveal()));
        }

        $this->viewHelper->method("getTypoScriptFrontendController")->will($this->returnValue($tsfe->reveal()));

        $this->assertEquals(1, $this->viewHelper->render());
    }

    /**
     * @test
     */
    public function returnsZeroIfTsfeDoesNotExist()
    {
        $this->viewHelper->method("getTypoScriptFrontendController")->will($this->returnValue(null));

        $this->assertEquals(0, $this->viewHelper->render());
    }
}

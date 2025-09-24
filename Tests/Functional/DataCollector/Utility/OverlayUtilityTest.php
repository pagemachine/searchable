<?php

declare(strict_types=1);

namespace PAGEmachine\Searchable\Tests\Functional\Service;

use PAGEmachine\Searchable\DataCollector\Utility\OverlayUtility;
use PAGEmachine\Searchable\Tests\Functional\AbstractElasticsearchTest;
use PAGEmachine\Searchable\Utility\TsfeUtility;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;

/**
 * Testcase for PAGEmachine\Searchable\DataCollector\Utility\OverlayUtilityTest
 */
final class OverlayUtilityTest extends AbstractElasticsearchTest
{
    /**
     * @var OverlayUtility
     */
    protected $OverlayUtility;

    /**
     * @test
     */
    public function overlayWithFallbackTypeStrict(): void
    {
        $record = [
            'uid' => 300,
            'pid' => 1,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'EN only page',
            'slug' => '/en-only/',
        ];

        $this->writeSiteConfiguration(
            '1',
            $this->buildSiteConfiguration(1, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de/', [], 'strict'),
            ]
        );

        $this->insertArray('pages', $record);

        // EN
        TsfeUtility::createTSFE('1', 0);
        $recordOverlay = $this->OverlayUtility->pagesLanguageOverlay(
            $record,
            0
        );
        $this->assertEquals(300, $recordOverlay['uid']);

        // DE
        TsfeUtility::createTSFE('1', 1);
        $this->expectException(\Exception::class);
        $recordOverlay = $this->OverlayUtility->pagesLanguageOverlay(
            $record,
            1
        );
    }

    /**
     * @test
     */
    public function overlayWithFallbackTypeFallback(): void
    {
        $record = [
            'uid' => 300,
            'pid' => 1,
            'doktype' => PageRepository::DOKTYPE_DEFAULT,
            'title' => 'EN only page',
            'slug' => '/en-only/',
        ];

        $this->writeSiteConfiguration(
            '1',
            $this->buildSiteConfiguration(1, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de/', ['EN'], 'fallback'),
            ]
        );

        $this->insertArray('pages', $record);

        // EN
        TsfeUtility::createTSFE('1', 0);
        $recordOverlay = $this->OverlayUtility->pagesLanguageOverlay(
            $record,
            0
        );
        $this->assertEquals(300, $recordOverlay['uid']);

        // DE
        TsfeUtility::createTSFE('1', 1);
        $recordOverlay = $this->OverlayUtility->pagesLanguageOverlay(
            $record,
            1
        );
        $this->assertEquals(300, $recordOverlay['uid']);
    }

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->OverlayUtility = $this->get(OverlayUtility::class);
    }
}

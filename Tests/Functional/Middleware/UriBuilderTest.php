<?php
declare(strict_types = 1);

namespace PAGEmachine\Searchable\Tests\Functional\Middleware;

use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use PAGEmachine\Searchable\Tests\Functional\WebserverTrait;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Http\RequestFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

/**
 * Testcase for PAGEmachine\Searchable\Middleware\UriBuilder
 */
final class UriBuilderTest extends FunctionalTestCase
{
    use WebserverTrait;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/searchable',
    ];

    /**
     * @test
     */
    public function buildsUriForTypolinkParameter(): void
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getConnectionForTable('pages');
        $connection->bulkInsert(
            'pages',
            [
                [
                    'uid' => 1,
                    'pid' => 0,
                    'title' => 'Root page',
                    'slug' => '/',
                ],
                [
                    'uid' => 2,
                    'pid' => 1,
                    'title' => 'Some page',
                    'slug' => '/some-page/',
                ],
                [
                    'uid' => 3,
                    'pid' => 1,
                    'title' => 'Other page',
                    'slug' => '/other-page/',
                ],
                [
                    'uid' => 4,
                    'pid' => 1,
                    'title' => 'Nested page',
                    'slug' => '/some-page/nested-page/',
                ],
            ],
            [
                'uid',
                'pid',
                'title',
                'slug',
            ]
        );

        $this->setUpFrontendRootPage(1);

        $response = GeneralUtility::makeInstance(RequestFactory::class)->request(
            'http://localhost:8080/-/searchable/urls',
            'POST',
            [
                'form_params' => [
                    'configurations' => [
                        [
                            'parameter' => 3,
                        ],
                        [
                            'parameter' => 2,
                        ],
                        [
                            'parameter' => 4,
                        ],
                    ],
                ],
            ]
        );
        $result = json_decode((string)$response->getBody(), true);
        $expected = [
            '/other-page/',
            '/some-page/',
            '/some-page/nested-page/',
        ];

        $this->assertEquals($expected, $result);
    }

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        if (version_compare(VersionNumberUtility::getCurrentTypo3Version(), '9', '<')) {
            $this->markTestSkipped('TYPO3v9+ only');
        }

        $this->startWebserver();
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        $this->stopWebserver();
    }
}

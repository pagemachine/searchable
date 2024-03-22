<?php
namespace PAGEmachine\Searchable\DataCollector;

use PAGEmachine\Searchable\Feature\CompletionSuggestFeature;
use PAGEmachine\Searchable\Feature\HtmlStripFeature;
use PAGEmachine\Searchable\DataCollector\RelationResolver\TtContentRelationResolver;
use PAGEmachine\Searchable\DataCollector\Utility\OverlayUtility;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\RootlineUtility;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Class for fetching pages data
 */
class PagesDataCollector extends TcaDataCollector implements DataCollectorInterface
{
    protected static $defaultConfiguration = [
        'table' => 'pages',
        'pid' => 0,
        'sysLanguageOverlay' => 1,
        'doktypes' => ['1'],
        'transientDoktypes' => ['4', '199'],
        'groupWhereClause' => ' AND (pages.fe_group = "" OR pages.fe_group = 0)',
        'includeHideInMenu' => false,
        'mode' => 'whitelist',
        'fields' => [
            'title',
        ],
        'features' => [
            'completion' => [
                'className' => CompletionSuggestFeature::class,
                'config' => [
                    'fields' => [
                        'title',
                    ],
                ],
            ],
            'htmlStrip' => [
                'className' => HtmlStripFeature::class,
            ],
        ],
        'subCollectors' => [
            'content' => [
                'className' => TcaDataCollector::class,
                'config' => [
                    'field' => 'content',
                    'table' => 'tt_content',
                    'resolver' => [
                        'className' => TtContentRelationResolver::class,
                    ],
                    'fields' => [
                        'header',
                        'subheader',
                        'bodytext',
                    ],
                ],
            ],

        ],
    ];

    /**
     * @var PageRepository $pageRepository
     */
    protected $pageRepository;

    /**
     * @param PageRepository $pageRepository
     */
    public function injectPageRepository(PageRepository $pageRepository): void
    {
        $this->pageRepository = $pageRepository;
    }

    /**
     * Cache string for database merged doktypes
     *
     * @var string|null
     */
    protected $doktypes = null;

    /**
     * Returns the doktypes needed for db fetching
     *
     * @return string
     */
    public function getDoktypes()
    {
        if ($this->doktypes == null) {
            $this->doktypes = implode(
                ",",
                array_merge($this->config['doktypes'], $this->config['transientDoktypes'])
            );
        }

        return $this->doktypes;
    }

    /**
     *
     *
     * @return \Generator
     */
    public function getRecords()
    {
        foreach ($this->getPageRecords($this->config['pid']) as $page) {
            yield $page;
        }
    }

    /**
     *
     *
     * @return \Generator|null
     */
    protected function getPageRecords($pid = null)
    {
        $whereClause =
            ' AND pages.hidden = 0' .
            ' AND pages.doktype IN(' . $this->getDoktypes() . ')' .
            $this->config['groupWhereClause'] .
            ($this->config['includeHideInMenu'] ? '' : ' AND pages.nav_hide = 0')
            ;

        $rawList = $this->pageRepository->getMenu(
            $pid,
            implode(',', [
                'uid',
                'doktype',
                'shortcut',
                'shortcut_mode',
                'no_search',
            ]),
            'sorting',
            $whereClause
        );

        if (!empty($rawList)) {
            foreach ($rawList as $uid => $page) {
                // Check if page is directly indexable or only transient,
                // also skip page if search has been disabled
                if (in_array($page['doktype'], $this->config['doktypes']) && !($page['no_search'] ?? false)) {
                    yield $this->getRecord($uid);
                }

                //@todo: use "yield from" as soon as PHP7 is a requirement
                $subpages = $this->getPageRecords($uid);

                if (!empty($subpages)) {
                    foreach ($subpages as $page) {
                        yield $page;
                    }
                }
            }
        }
    }

    /**
     * Unset pid (works differently with pages and should not be taken into account)
     * Add doktype where clause
     *
     * @param  array $updateUidList
     * @return \Generator
     */
    public function getUpdatedRecords($updateUidList)
    {
        $updateUidList = $this->filterPageListByRootline($updateUidList, $this->config['pid']);

        $this->config['pid'] = null;
        $this->config['select']['additionalWhereClauses']['doktypes'] = ' AND pages.doktype IN(' . implode(",", $this->config['doktypes']) . ')';

        foreach (parent::getUpdatedRecords($updateUidList) as $record) {
            yield $record;
        }
    }

    /**
     * Get overlay
     *
     * @param  array $record
     * @return array
     */
    protected function languageOverlay($record)
    {
        return OverlayUtility::getInstance()->pagesLanguageOverlay($record, $this->language, $this->config['sysLanguageOverlay']);
    }

    /**
     * Returns a list of page UIDs that are part of the given rootline page
     *
     * @return array
     */
    protected function filterPageListByRootline(array $pageUids, int $rootlinePageUid): array
    {
        $filteredPageUids = [];

        foreach ($pageUids as $uid) {
            try {
                $rootLine = GeneralUtility::makeInstance(RootlineUtility::class, $uid)->get();

                if (in_array($rootlinePageUid, array_column($rootLine, 'uid'), true)) {
                    $filteredPageUids[] = $uid;
                }
            } catch (\RuntimeException) {
                // If the page is deleted, RootlineUtility will throw a RuntimeException.
                // We still queue the uid then to ensure it gets deleted in ES as well
                $filteredPageUids[] = $uid;
            }
        }

        return $filteredPageUids;
    }
}

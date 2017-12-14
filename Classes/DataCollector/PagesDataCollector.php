<?php
namespace PAGEmachine\Searchable\DataCollector;

use PAGEmachine\Searchable\DataCollector\Utility\OverlayUtility;

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
                'className' => \PAGEmachine\Searchable\Feature\CompletionSuggestFeature::class,
                'config' => [
                    'fields' => [
                        'title',
                    ],
                ],
            ],
        ],
        'subCollectors' => [
            'content' => [
                'className' => \PAGEmachine\Searchable\DataCollector\TcaDataCollector::class,
                'config' => [
                    'field' => 'content',
                    'table' => 'tt_content',
                    'resolver' => [
                        'className' => \PAGEmachine\Searchable\DataCollector\RelationResolver\TtContentRelationResolver::class,
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
     * @return \Generator
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
            'uid, doktype, shortcut, shortcut_mode',
            'sorting',
            $whereClause
        );

        if (!empty($rawList)) {
            foreach ($rawList as $uid => $page) {
                //Check if doktype is indexable or transient
                if (in_array($page['doktype'], $this->config['doktypes'])) {
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
     * @todo Check for rootline if we want to be extra precise
     *
     * @param  array $updateUidList
     * @return \Generator
     */
    public function getUpdatedRecords($updateUidList)
    {
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
    protected function languageoverlay($record)
    {
        return OverlayUtility::getInstance()->pagesLanguageOverlay($record, $this->language);
    }
}

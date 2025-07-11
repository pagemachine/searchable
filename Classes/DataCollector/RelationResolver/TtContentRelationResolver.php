<?php
namespace PAGEmachine\Searchable\DataCollector\RelationResolver;

use PAGEmachine\Searchable\DataCollector\AbstractDataCollector;
use PAGEmachine\Searchable\DataCollector\DataCollectorInterface;
use PAGEmachine\Searchable\DataCollector\RelationResolver\RelationResolverInterface;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * This file is part of the Pagemachine Searchable project.
 */

/**
 *
 */
class TtContentRelationResolver implements SingletonInterface, RelationResolverInterface
{
    /**
     *
     * @var \TYPO3\CMS\Core\Domain\Repository\PageRepository
     */
    protected $pageRepository;

    /**
     *
     * @return TtContentRelationResolver
     */
    public static function getInstance()
    {
        return GeneralUtility::makeInstance(self::class);
    }

    /**
     * @param PageRepository|null $pageRepository
     */
    public function __construct(PageRepository $pageRepository = null)
    {
        $this->pageRepository = $pageRepository ?: GeneralUtility::makeInstance(PageRepository::class);
    }

    /**
     * Resolves a relation between pages and content
     *
     * @param  string $fieldname
     * @param  array $record The record containing the field to resolve
     * @param  DataCollectorInterface $childCollector
     * @param  DataCollectorInterface $parentCollector
     * @return array $processedField
     */
    public function resolveRelation($fieldname, $record, DataCollectorInterface $childCollector, DataCollectorInterface $parentCollector)
    {
        $processedField = [];
        $language = null;

        if (!$childCollector->getConfig()['sysLanguageOverlay'] && $childCollector instanceof AbstractDataCollector) {
            $language = (string)$childCollector->getLanguage();
        }

        $contentUids = $this->fetchContentUids(
            $record['uid'],
            $language,
            $childCollector->getConfig()['select']['additionalWhereClauses'] ?? [],
        );

        while ($row = $contentUids->fetchAssociative()) {
            $processedField[] = $childCollector->getRecord($row['uid']);
        }

        return $processedField;
    }

    /**
     * Fetches content uids to transfer to datacollector
     *
     * @param  int $pid
     * @param  string $languages Language constraint, if null default is assumed (0,-1)
     */
    protected function fetchContentUids($pid, $languages = null, array $additionalWhereClauses = [])
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $queryBuilder->select('uid')
            ->from('tt_content');

        $whereExpressions = [
            $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pid)),
            $queryBuilder->expr()->in($GLOBALS['TCA']['tt_content']['ctrl']['languageField'], $languages ?: '0,-1'),
        ];

        if (!empty($additionalWhereClauses)) {
            $whereExpressions[] = QueryHelper::stripLogicalOperatorPrefix(
                implode('', $additionalWhereClauses)
            );
        }

        $queryBuilder->where(...$whereExpressions);

        return $queryBuilder->executeQuery();
    }
}

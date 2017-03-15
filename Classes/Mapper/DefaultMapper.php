<?php
namespace PAGEmachine\Searchable\Mapper;

use PAGEmachine\Searchable\Indexer\IndexerInterface;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * DefaultMapper
 * This mapper just looks into the given indexer configuration ("mapping" section) and returns the sub-array
 * It is set by default and can be used as a base for custom mappers
 */
class DefaultMapper implements SingletonInterface, MapperInterface {

    /**
     * @return DefaultMapper
     */
    public static function getInstance() {

        return GeneralUtility::makeInstance(DefaultMapper::class);
    }

    /**
     * Creates a mapping array for the given index configuration
     *
     * @param  IndexerInterface $indexer
     * @return array $mapping
     */
    public function createMapping(IndexerInterface $indexer) {

        $configuration = $indexer->getConfig();

        $mapping = (!empty($configuration['mapping'])) ? $configuration['mapping'] : [];

        return $mapping;
    }

}

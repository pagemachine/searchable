<?php
namespace PAGEmachine\Searchable\Command;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

use PAGEmachine\Searchable\Service\IndexingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\CommandController;

class SearchableCommandController extends CommandController
{
    /**
     * @var bool
     */
    protected $requestAdminPermissions = true;

    /**
     * @var IndexingService $indexingService
     */
    protected $indexingService;

    /**
     * @param IndexingService $indexingService
     */
    public function injectIndexingService(IndexingService $indexingService): void
    {
        $this->indexingService = $indexingService;
    }

    /**
     * Runs all indexers (full)
     * @param  string $type If set, only runs indexing for the given type
     * @return void
     * @deprecated since Searchable v3, will be removed with Searchable v4, use "index:update:full" instead
     */
    public function indexFullCommand($type = null)
    {
        // @extensionScannerIgnoreLine
        GeneralUtility::logDeprecatedFunction();

        $this->indexingService->indexFull($type);
    }

    /**
     * Runs all indexers (updates only)
     * @param  string $type If set, only runs indexing for the given type
     * @return void
     * @deprecated since Searchable v3, will be removed with Searchable v4, use "index:update:partial" instead
     */
    public function indexPartialCommand($type = null)
    {
        // @extensionScannerIgnoreLine
        GeneralUtility::logDeprecatedFunction();

        $this->indexingService->indexPartial($type);
    }

    /**
     * Reset index for one or all languages
     *
     * @param int $language
     * @return void
     * @deprecated since Searchable v3, will be removed with Searchable v4, use "index:reset" instead
     */
    public function resetIndexCommand($language = null)
    {
        // @extensionScannerIgnoreLine
        GeneralUtility::logDeprecatedFunction();

        $this->indexingService->resetIndex($language);
    }

    /**
     * Sets up everything, needs to be run after installation.
     * Can be run multiple times to ensure correct setup.
     *
     * @return void
     * @deprecated since Searchable v3, will be removed with Searchable v4, use "index:setup" instead
     */
    public function setupCommand()
    {
        // @extensionScannerIgnoreLine
        GeneralUtility::logDeprecatedFunction();

        $this->indexingService->setup();
    }
}

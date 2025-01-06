<?php
declare(strict_types = 1);

namespace PAGEmachine\Searchable\Command\Index;

use PAGEmachine\Searchable\Service\IndexingService;
use Symfony\Component\Console\Command\Command;
use TYPO3\CMS\Core\Authentication\CommandLineUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\VisibilityAspect;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class AbstractIndexCommand extends Command
{
    /**
     * @var IndexingService
     */
    protected $indexingService;

    public function __construct(...$arguments)
    {
        parent::__construct(...$arguments);

        $this->indexingService = GeneralUtility::makeInstance(IndexingService::class);

        Bootstrap::initializeBackendAuthentication();

        if ($GLOBALS['BE_USER'] instanceof CommandLineUserAuthentication) {
            $GLOBALS['BE_USER']->initializeUserSessionManager();
        }

        $context = GeneralUtility::makeInstance(Context::class);
        $currentVisibilityAspect = $context->getAspect('visibility');
        if ((new Typo3Version())->getMajorVersion() < 12) {
            $context->setAspect('visibility', new VisibilityAspect(
                includeHiddenPages: $currentVisibilityAspect->includeHiddenPages(),
                includeHiddenContent: false,
                includeDeletedRecords: $currentVisibilityAspect->includeDeletedRecords(),
            ));
        } else {
            $context->setAspect('visibility', new VisibilityAspect(
                includeHiddenPages: $currentVisibilityAspect->includeHiddenPages(),
                includeHiddenContent: false,
                includeDeletedRecords: $currentVisibilityAspect->includeDeletedRecords(),
                includeScheduledRecords: $currentVisibilityAspect->includeScheduledRecords(),
            ));
        }
    }
}

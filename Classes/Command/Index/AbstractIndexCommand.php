<?php
declare(strict_types = 1);

namespace PAGEmachine\Searchable\Command\Index;

use PAGEmachine\Searchable\Service\IndexingService;
use Symfony\Component\Console\Command\Command;
use TYPO3\CMS\Core\Authentication\CommandLineUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\VisibilityAspect;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\Writer\PhpErrorLogWriter;
use TYPO3\CMS\Core\Utility\GeneralUtility;

abstract class AbstractIndexCommand extends Command
{
    /**
     * @var IndexingService
     */
    protected $indexingService;

    public function __construct(...$arguments)
    {
        if (Environment::isCli()) {
            $GLOBALS['TYPO3_CONF_VARS']['LOG']['PAGEmachine']['Searchable']['writerConfiguration'][LogLevel::INFO][PhpErrorLogWriter::class] = [];
        }

        parent::__construct(...$arguments);

        $this->indexingService = GeneralUtility::makeInstance(IndexingService::class);

        Bootstrap::initializeBackendAuthentication();

        if ($GLOBALS['BE_USER'] instanceof CommandLineUserAuthentication) {
            $GLOBALS['BE_USER']->initializeUserSessionManager();
        }

        $context = GeneralUtility::makeInstance(Context::class);
        $currentVisibilityAspect = $context->getAspect('visibility');
        $context->setAspect('visibility', new VisibilityAspect(
            includeHiddenPages: true,
            includeHiddenContent: false,
            includeDeletedRecords: $currentVisibilityAspect->get('includeDeletedRecords'),
            includeScheduledRecords: $currentVisibilityAspect->get('includeScheduledRecords'),
        ));
    }
}

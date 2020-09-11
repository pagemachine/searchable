<?php
declare(strict_types = 1);

namespace PAGEmachine\Searchable\Command\Legacy;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @deprecated will be removed with Searchable v4
 */
abstract class AbstractLegacyCommand extends Command
{
    /**
     * The replacement for this deprecated legacy command
     *
     * @var string
     */
    protected $replacementCommand;

    /**
     * @return string
     */
    public function getDescription()
    {
        return sprintf(
            '%s <comment>(deprecated)</>',
            $this->getApplication()->find($this->replacementCommand)->getDescription()
        );
    }

    /**
     * @return \Symfony\Component\Console\Input\InputDefinition
     */
    public function getDefinition()
    {
        return $this->getApplication()->find($this->replacementCommand)->getDefinition();
    }

    /**
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $deprecationMessage = sprintf(
            'The CLI command "%s" is deprecated and will be removed with Searchable v4, use "%s" instead',
            $this->getName(),
            $this->replacementCommand
        );

        if (!method_exists(GeneralUtility::class, 'deprecationLog')) { // TYPO3v10
            trigger_error($deprecationMessage, E_USER_DEPRECATED);
        } else {
            // @extensionScannerIgnoreLine
            GeneralUtility::deprecationLog($deprecationMessage);
        }

        return $this->getApplication()->find($this->replacementCommand)->run($input, $output);
    }
}

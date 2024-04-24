<?php
declare(strict_types = 1);

namespace Pagemachine\SearchableExtbaseL10nTest\Preview;

use PAGEmachine\Searchable\Preview\AbstractPreviewRenderer;
use PAGEmachine\Searchable\Preview\PreviewRendererInterface;
use Pagemachine\SearchableExtbaseL10nTest\Domain\Repository\ContentRepository;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final class ContentPreviewRenderer extends AbstractPreviewRenderer implements PreviewRendererInterface
{
    /**
     * @param  array $record
     * @return string
     */
    public function render($record)
    {
        $repository = GeneralUtility::makeInstance(ContentRepository::class);
        $content = $repository->findByIdentifier($record['uid']);
        $preview = sprintf(
            'Preview: %s [%d]',
            $content->getHeader(),
            $content->_getProperty('_localizedUid')
        );

        return $preview;
    }
}

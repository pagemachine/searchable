<?php
declare(strict_types = 1);

namespace Pagemachine\SearchableExtbaseL10nTest\Preview;

use PAGEmachine\Searchable\Preview\AbstractPreviewRenderer;
use PAGEmachine\Searchable\Preview\PreviewRendererInterface;
use Pagemachine\SearchableExtbaseL10nTest\Domain\Repository\ContentRepository;

final class ContentPreviewRenderer extends AbstractPreviewRenderer implements PreviewRendererInterface
{
    protected ?ContentRepository $contentRepository = null;

    public function injectContentRepository(ContentRepository $contentRepository)
    {
        $this->contentRepository = $contentRepository;
    }

    /**
     * @param  array $record
     * @return string
     */
    public function render($record)
    {
        $content = $this->contentRepository->findByIdentifier($record['uid']);
        $preview = sprintf(
            'Preview: %s [%d]',
            $content->getHeader(),
            $content->_getProperty('_localizedUid')
        );

        return $preview;
    }
}

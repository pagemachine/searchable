<?php
namespace PAGEmachine\Searchable\Preview;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * NoPreviewRenderer
 * Simply returns an empty string. Use this renderer if you only want to return highlighted results
 */
class NoPreviewRenderer extends AbstractPreviewRenderer implements PreviewRendererInterface
{
    /**
     * Renders the preview
     *
     * @param  array $record
     * @return string
     */
    public function render($record)
    {
        return '';
    }
}

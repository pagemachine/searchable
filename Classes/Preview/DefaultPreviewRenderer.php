<?php
namespace PAGEmachine\Searchable\Preview;

/*
 * This file is part of the Pagemachine Searchable project.
 */

/**
 * Default preview renderer.
 */
class DefaultPreviewRenderer extends AbstractPreviewRenderer implements PreviewRendererInterface
{
    /**
     * Renders the preview
     *
     * @param  array $record
     * @return string
     */
    public function render($record)
    {
        $preview = implode(", ", $record);

        return $preview;
    }
}

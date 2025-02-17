<?php
namespace PAGEmachine\Searchable\Preview;

/*
 * This file is part of the Pagemachine Searchable project.
 */

interface PreviewRendererInterface
{
    /**
     * Renders the preview
     *
     * @param  array $record
     * @return string
     */
    public function render($record);
}

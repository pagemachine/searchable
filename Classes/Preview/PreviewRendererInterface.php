<?php
namespace PAGEmachine\Searchable\Preview;

/*
 * This file is part of the PAGEmachine Searchable project.
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

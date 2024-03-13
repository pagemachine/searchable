<?php
namespace PAGEmachine\Searchable\Preview;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Simple preview renderer.
 */
class SimplePreviewRenderer extends AbstractPreviewRenderer implements PreviewRendererInterface
{
    /**
     * @var array
     */
    protected $config;

    /**
     * @param array $config
     */
    public function __construct($config)
    {
        $this->config = $config;
    }

    /**
     * Renders the preview
     *
     * @param  array $record
     * @return string
     */
    public function render($record)
    {
        $rawfield = $record[$this->config['field']];

        $processedField = substr((string) $rawfield, 0, 200) . "...";

        return $processedField;
    }
}

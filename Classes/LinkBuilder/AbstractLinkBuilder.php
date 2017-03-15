<?php
namespace PAGEmachine\Searchable\LinkBuilder;

use PAGEmachine\Searchable\Service\ConfigurationMergerService;


/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * AbstractLinkBuilder
 */
abstract class AbstractLinkBuilder {

    /**
     * @var array
     */
    protected $config = [];

    /**
     * @param array $config
     */
    public function __construct($config = null) {

        if ($config != null) {

            $this->config = ConfigurationMergerService::merge($this->config, $config);
        }
    }
}

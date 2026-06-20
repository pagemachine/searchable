<?php

namespace PAGEmachine\Searchable;

use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use PAGEmachine\Searchable\Service\ExtconfService;

/*
 * This file is part of the Pagemachine Searchable project.
 */

/**
 * Manages the ES connection with settings
 */
class Connection
{
    /**
     * The cached ES client
     *
     * @var Client
     */
    protected static $client;

    /**
     * Returns the (configured) ES Client
     *
     * @return Client
     */
    public static function getClient()
    {
        if (self::$client == null) {
            self::$client = ClientBuilder::create()
                ->setHosts(ExtconfService::getInstance()->getHostsSettings())
                ->build();
        }
        return self::$client;
    }

    /**
     * Tries to (re-)build the client to check if nodes are available
     *
     * @return bool
     */
    public static function isHealthy()
    {
        return self::getClient()->ping()->asBool();
    }
}

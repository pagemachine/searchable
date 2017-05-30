<?php
namespace PAGEmachine\Searchable;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use PAGEmachine\Searchable\Service\ExtconfService;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Manages the ES connection with settings
 */
class Connection {

    /**
     * The cached ES client
     *
     * @var Client
     */
    protected static $client = null;


    /**
     * Returns the (configured) ES Client
     *
     * @return Client
     */
    public static function getClient()
    {
        if (self::$client == null) {

            self::$client = ClientBuilder::create()
                ->setHosts(
                    ExtconfService::getInstance()
                    ->getHostsSettings()
                )->build();
        }

        return self::$client;

    }


}

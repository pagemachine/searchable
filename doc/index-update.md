# Index Updating

Indices must be updated regularly, to reflect changes to records in typo3.

## Full updates

After the [index setup](index-setup.md) you should run the following command once to perform a full index update:

    typo3 index:update:full

This can take some time because it processes all records every time. To reduce runtime during regular operation, setup and run partial updates regularly.

## Partial updates

To perform partial index updates run the following command:

    typo3 index:update:partial

It is recommended to set up a Scheduler task to execute this command periodically, for example every 5 minutes.

You need to add a database connection `wrapperClass` in your `LocalConfiguration.php`:


```php

    // ...
    'DB' => [
        'Connections' => [
            'Default' => [
                'host' => ...,
                'dbname' => ...,
                'user' => ...,
                'password' => ...,
                'wrapperClass' => \PAGEmachine\Searchable\Database\Connection::class,
            ],
        ],
    ],
    // ...
```

## Reset index

If you need to start from scratch, run the following commands:

    typo3 index:reset
    typo3 index:update:full

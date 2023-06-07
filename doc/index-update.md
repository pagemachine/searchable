# Index Updating

To actually allow for searching indices must be updated regularly whenever records are added, changed or deleted.

## Full updates

After index setup [setup](index-setup.md) you should run the following command once to perform a full index update:

    typo3cms searchable:indexfull

This takes a while since this processes all records of types in the configuration. To speed this up, partial updates can be performed.

## Partial updates

To perform partial index updates run the following command:

    typo3cms searchable:indexpartial

You should set up a Scheduler task using the _Extbase CommandController Task_ to execute this periodically, e.g. every 5 minutes.

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

If you ever need to start from scratch you can run the following commands:

    typo3cms searchable:resetindex
    typo3cms searchable:indexfull

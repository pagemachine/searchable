# Extension Manager settings

99% of the configuration is directly defined via `$_EXTCONF` and without using the EM settings.
However, some settings may depend on the current environment (*dev*, *staging*, *production*). All of these settings are defined via Extension Manager so you can easily override them for each environment/context.

See `ext_conf_template.txt` for the desired format.


## Connection

Elasticsearch connection settings.

These settings are written to `$_EXTCONF['connection.']`.

### Hosts

The `hosts` key supports a comma-separated list of host URIs.

All available options can be found in the [elasticsearch-PHP configuration docs](https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/_configuration.html#_inline_host_configuration).

## Indexing settings

### Domain

Since the LinkBuilding engine calls a eID handler to have a valid frontend environment, it needs to know which domain to call.
The `domain` key should contain a valid domain to call this eID on.

All available options can be found in the [elasticsearch-PHP configuration docs](https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/_configuration.html#_inline_host_configuration).


# File Indexing

Elasticsearch can index file content via ingest attachment plugin.

You need to install the plugin and also create a pipeline, like this:

`PUT http://yourhost:port/_ingest/pipeline/attachment`

    {
        "description": "Extract attachment information from arrays",
        "processors": [
            {
                "foreach": {
                    "field": "attachments",
                    "processor": {
                        "attachment": {
                            "target_field": "_ingest._value.attachment",
                            "field": "_ingest._value.data",
                            "indexed_chars": -1
                        }
                    }
                }
            },
            {
                "foreach": {
                    "field": "attachments",
                    "processor": {
                        "remove": { "field": "_ingest._value.data"}
                    }
                }
            }
        ]
    }

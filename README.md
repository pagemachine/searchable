# Searchable ![CI](https://github.com/pagemachine/searchable/workflows/CI/badge.svg)

This extension adds Elasticsearch-based indexing and search to TYPO3, with flexible extension points for complex projects.

Features:

* Out-of-the-box page indexing
* Easy support for extension content through TCA-based indexing
* Multilanguage support
* Command-line indexing
* Highly configurable
* Easily extendable with your own logic

The extension is designed to adapt to project-specific requirements.
Instead of writing a complete indexer from scratch, you can register custom classes for nearly every sub-process, including preview rendering, link generation, and more.

## Documentation

The full documentation can be found [here](doc/index.md).

## Installation

You can install this extension from multiple sources:

1. Via [Composer](https://packagist.org/packages/pagemachine/searchable):

        composer require pagemachine/searchable

2. From the [TYPO3 Extension Repository](https://extensions.typo3.org/extension/searchable/)
3. From [GitHub](https://github.com/pagemachine/searchable/releases)

## Testing

All tests can be executed with the shipped Docker Compose definition:

    docker compose run --rm app composer build

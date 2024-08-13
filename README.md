# Searchable ![CI](https://github.com/pagemachine/searchable/workflows/CI/badge.svg)

This extension provides an API to easily use Elasticsearch in complex TYPO3 instances.

Features:

* Out-of-the box indexing of pages
* Indexing of extension content via minimal configuration (TCA based indexing)
* Multilanguage support
* Command-line indexing
* Highly configurable
* Highly extendable with your own code

In addition to the already supported features, it is very easy to add your own code if necessary.
No need to write a complete indexer from scratch, instead you can register custom classes for almost every sub-process like preview rendering, link generation and more.

## Testing

All tests can be executed with the shipped Docker Compose definition:

    docker compose run --rm app composer build

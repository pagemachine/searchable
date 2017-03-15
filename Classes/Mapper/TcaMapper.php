<?php
namespace PAGEmachine\Searchable\Mapper;

use PAGEmachine\Searchable\Enumeration\TcaType;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * TCA based mapper, creates the index mapping based on the given TCA
 */
class TcaMapper implements SingletonInterface, MapperInterface {

    /**
     * @return TcaMapper
     */
    public static function getInstance() {

        return GeneralUtility::makeInstance(TcaMapper::class);
    }

    /**
     * Creates a mapping array for the given index configuration
     *
     * @param  array  $indexerConfiguration
     * @return array $mapping
     */
    public function createMapping($indexerConfiguration = []) {

        $mapping = $this->createMappingForType($indexerConfiguration['type'], $indexerConfiguration['config']['table'], $indexerConfiguration);

        $mapping['_source'] = ['enabled' => true];

        return $mapping;




    }

    /**
     * Creates mapping for a given type.
     * Recursive, fetches subtypes as well.
     *
     * @param  string $type
     * @param  string $table
     * @param  array $configuration
     * @return array
     */
    public function createMappingForType($type, $table, $configuration) {

        $mapping = [
            'properties' => []
        ];

        $tca = $GLOBALS['TCA'][$table];

        // Fetch plain types
        foreach ($tca['columns'] as $fieldname => $column) {

            $columnType = TcaType::cast($column['config']['type']);

            if ($columnType->isPlainMappingType() && !in_array($fieldname, $configuration['config']['excludeFields'])) {

                $mapping['properties'][$fieldname] = [
                    'type' => $columnType->convertToESType()
                ];
            }
        }

        // Fetch relation types
        foreach ($configuration['subtypes'] as $subtypeName => $subtypeConfiguration) {

            $foreignTable = $tca['columns'][$subtypeConfiguration[$field]]['config']['foreign_table'];

            if ($foreignTable) {

                //By default subtypes are indexed as object, not as nested type since nested types cause indices to explode quickly in size
                //See https://www.elastic.co/guide/en/elasticsearch/reference/current/nested.html
                $mapping['properties'][$subtypeName] = $this->createMappingForType($subtypeConfiguration['field'], $foreignTable, $subtypeConfiguration);       
            }

        }

        return $mapping;
    }


}

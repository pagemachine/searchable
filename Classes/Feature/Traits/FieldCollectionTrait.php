<?php
namespace PAGEmachine\Searchable\Feature\Traits;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * FieldCollectionTrait
 */
trait FieldCollectionTrait
{
    /**
     * function to collect fields from a record
     *
     * @param array $record
     * @param array $fields
     */
    protected function collectFields($record, $fields, $content = [])
    {
        $content = [];

        foreach ($fields as $field) {
            if (array_key_exists($field, $record) && !empty($record[$field])) {
                $content[] = $record[$field];
            }
        }

        return $content;
    }

    /**
     * Collects a field from Subrecords
     *
     * @param  array $record
     * @param  string $fieldname
     * @param  array  $collection
     * @param  bool  $unset
     * @return array $collection
     */
    protected function collectFieldFromSubRecords(&$record, $fieldname, $collection = [], $unset = false)
    {
        foreach ($record as $column => $value) {
            if (is_array($value) && !isset($value['uid'])) {
                foreach ($value as $childKey => $childRecord) {
                    if (!empty($childRecord[$fieldname])) {
                        $collection = $this->mergeOrAddField($collection, $childRecord[$fieldname]);

                        if ($unset) {
                            unset($record[$column][$childKey][$fieldname]);
                        }
                    }
                }
            } elseif (is_array($value)) {
                if (!empty($value[$fieldname])) {
                    $collection = $this->mergeOrAddField($collection, $value[$fieldname]);

                    if ($unset) {
                        unset($record[$column][$fieldname]);
                    }
                }
            }
        }

        return $collection;
    }

    /**
     *
     * @param array $collection
     * @param mixed $field
     * @return array $collection
     */
    protected function mergeOrAddField($collection, mixed $field)
    {
        if (is_array($field)) {
            $collection = array_merge($collection, $field);
        } else {
            $collection[] = $field;
        }

        return $collection;
    }
}

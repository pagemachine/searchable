<?php
namespace PAGEmachine\Searchable\Service;

/*
 * This file is part of the Pagemachine Searchable project.
 */

/**
 * Class ConfigurationMergerService
 */
class ConfigurationMergerService
{
    /**
     * Recursively merge two config arrays with a specific behavior:
     *
     * 1. scalar values are overridden
     * 2. array values are extended uniquely if all keys are numeric
     * 3. all other array values are merged
     *
     * @return array
     * @see http://stackoverflow.com/a/36366886/6812729
     */
    public static function merge(array $original, array $override)
    {
        foreach ($override as $key => $value) {
            if (isset($original[$key])) {
                if (!is_array($original[$key])) {
                    // Override scalar value
                    $original[$key] = $value;
                } elseif (array_keys($original[$key]) === range(0, count($original[$key]) - 1)) {
                    // Uniquely append to array with numeric keys
                    $original[$key] = array_unique(array_merge($original[$key], $value));
                } else {
                    // Merge all other arrays
                    $original[$key] = ConfigurationMergerService::merge($original[$key], $value);
                }
            } else {
                // Simply add new key/value
                $original[$key] = $value;
            }
        }

        return $original;
    }
}

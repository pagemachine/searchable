<?php
namespace PAGEmachine\Searchable\Utility;

/*
 * This file is part of the Pagemachine Searchable project.
 */

/**
 * Helper class for all extconf related settings
 */
class BinaryConversionUtility
{
    /**
     * Converts binary ckeckbox values into an array containing all active keys
     *
     * @param  int $value the raw checkbox value
     * @param  int $itemCount max amount of items in this checkbox
     * @return array
     */
    public static function convertCheckboxValue($value, $itemCount = 31)
    {
        $checkedItemKeys = [];

        for ($i=0; $i < $itemCount; $i++) {
            $pow = 2 ** $i;
            if ($value & $pow) {
                $checkedItemKeys[] = $i;
            }
        }

        return $checkedItemKeys;
    }
}

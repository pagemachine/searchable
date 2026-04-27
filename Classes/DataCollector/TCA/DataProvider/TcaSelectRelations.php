<?php
namespace PAGEmachine\Searchable\DataCollector\TCA\DataProvider;

use TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;

/**
 * Custom implementation of TcaSelectItems. Resolves the relation only without fetching the whoule array of available items
 */
class TcaSelectRelations extends TcaSelectItems implements FormDataProviderInterface
{
    /**
     * Return empty array as we do not want to pull all foreign table items (performance)
     */
    protected function addItemsFromForeignTable(array $result, $fieldName, array $items = [], bool $includeFullRows = false): array
    {
        return $items;
    }
}

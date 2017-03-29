<?php
namespace PAGEmachine\Searchable\DataCollector\TCA\DataProvider;


use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems;

/**
 * Custom implementation of TcaSelectItems. Resolves the relation only without fetching the whoule array of available items
 */
class TcaSelectRelations extends TcaSelectItems implements FormDataProviderInterface
{

    /**
     * Return empty array as we do not want to pull all foreign table items (performance)
     *
     * @param array $result Result array
     * @param string $fieldName Current handle field name
     * @param array $items Incoming items
     * @return array Modified item array
     * @throws \UnexpectedValueException
     */
    protected function addItemsFromForeignTable(array $result, $fieldName, array $items)
    {
        return $items;
    }

}

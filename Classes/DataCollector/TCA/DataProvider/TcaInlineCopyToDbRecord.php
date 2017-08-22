<?php
namespace PAGEmachine\Searchable\DataCollector\TCA\DataProvider;

use TYPO3\CMS\Backend\Form\FormDataProvider\AbstractItemProvider;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;

/**
 * Custom implementation of TcaSelectItems. Resolves the relation only without fetching the whoule array of available items
 */
class TcaInlineCopyToDbRecord extends AbstractItemProvider implements FormDataProviderInterface
{
    /**
     * Resolve inline fields
     *
     * @param array $result
     * @return array
     */
    public function addData(array $result)
    {
        foreach ($result['processedTca']['columns'] as $fieldName => $fieldConfig) {
            if (!$this->isInlineField($fieldConfig)) {
                continue;
            }

            if (!empty($result['processedTca']['columns'][$fieldName]['children'])) {
                $result['databaseRow'][$fieldName] = [];

                foreach ($result['processedTca']['columns'][$fieldName]['children'] as $child) {
                    $result['databaseRow'][$fieldName][] = $child['databaseRow'];
                }
            }
        }

        return $result;
    }

    /**
     * Is column of type "inline"
     *
     * @param array $fieldConfig
     * @return bool
     */
    protected function isInlineField($fieldConfig)
    {
        return !empty($fieldConfig['config']['type']) && $fieldConfig['config']['type'] === 'inline';
    }
}

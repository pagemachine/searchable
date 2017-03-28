<?php
namespace PAGEmachine\Searchable\DataCollector\TCA;


use TYPO3\CMS\Backend\Form\FormDataGroupInterface;
use TYPO3\CMS\Backend\Form\FormDataProviderInterface;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Custom FormEngine FormDataGroup to pull only necessary data
 */
class SearchableRecordGroup implements FormDataGroupInterface
{
    /**
     * Compile form data
     *
     * @param array $result Initialized result array
     * @return array Result filled with data
     * @throws \UnexpectedValueException
     */
    public function compile(array $result)
    {
        $dataProvider = [
            \TYPO3\CMS\Backend\Form\FormDataProvider\DatabaseEditRow::class => [
                'depends' => []
            ],
            \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class => [
                'depends' => [
                    \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfig::class,
                ],
            ],
            \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems::class => [
                'depends' => [
                    // \TYPO3\CMS\Backend\Form\FormDataProvider\DatabasePageRootline::class,
                    // \TYPO3\CMS\Backend\Form\FormDataProvider\PageTsConfigMerged::class,
                    // \TYPO3\CMS\Backend\Form\FormDataProvider\InitializeProcessedTca::class,
                    // \TYPO3\CMS\Backend\Form\FormDataProvider\TcaTypesShowitem::class,
                    // \TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsRemoveUnused::class,
                    // \TYPO3\CMS\Backend\Form\FormDataProvider\TcaCheckboxItems::class,
                    // // GeneralUtility::getFlexFormDS() needs unchanged databaseRow values as string
                    // \TYPO3\CMS\Backend\Form\FormDataProvider\TcaFlexFetch::class,
                ],
            ],

        ];
        $orderingService = GeneralUtility::makeInstance(DependencyOrderingService::class);
        $orderedDataProvider = $orderingService->orderByDependencies($dataProvider, 'before', 'depends');

        foreach ($orderedDataProvider as $providerClassName => $_) {
            /** @var FormDataProviderInterface $provider */
            $provider = GeneralUtility::makeInstance($providerClassName);

            if (!$provider instanceof FormDataProviderInterface) {
                throw new \UnexpectedValueException(
                    'Data provider ' . $providerClassName . ' must implement FormDataProviderInterface',
                    1437906440
                );
            }

            $result = $provider->addData($result);
        }

        return $result;
    }
}

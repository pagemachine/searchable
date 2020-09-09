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
    protected $dataProviders = [];

    /**
     * @return void
     */
    public function __construct()
    {
        $dataProvider = $GLOBALS['TYPO3_CONF_VARS']['SYS']['formEngine']['formDataGroup']['tcaDatabaseRecord'];

        //Replace TcaSelectItems DataProvider with a custom one that does not fetch all available items for relations
        unset($dataProvider[\TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems::class]);
        $dataProvider[\PAGEmachine\Searchable\DataCollector\TCA\DataProvider\TcaSelectRelations::class] = [
            'depends' => [],
            'before' => [
                \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInputPlaceholders::class,
                \TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectTreeItems::class,
            ],
        ];


        //Unset DataProvider which determines the items to process. This information will be handed over in the compiler input array
        unset($dataProvider[\TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessShowitem::class]);

        //unset record title provider - no need
        unset($dataProvider[\TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessRecordTitle::class]);

        //Add custom inline provider to copy children to database record
        $dataProvider[\PAGEmachine\Searchable\DataCollector\TCA\DataProvider\TcaInlineCopyToDbRecord::class] = [
            'depends' => [
                \TYPO3\CMS\Backend\Form\FormDataProvider\TcaInline::class,
            ],
        ];


        $orderingService = GeneralUtility::makeInstance(DependencyOrderingService::class);
        $this->dataProviders = $orderingService->orderByDependencies($dataProvider, 'before', 'depends');
    }


    /**
     * Compile form data
     *
     * @param array $result Initialized result array
     * @return array Result filled with data
     * @throws \UnexpectedValueException
     */
    public function compile(array $result)
    {
        foreach ($this->dataProviders as $providerClassName => $_) {
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

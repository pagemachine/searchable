<?php
namespace PAGEmachine\Searchable\DataCollector\TCA;

use TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems;
use PAGEmachine\Searchable\DataCollector\TCA\DataProvider\TcaSelectRelations;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaInputPlaceholders;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectTreeItems;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessShowitem;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaColumnsProcessRecordTitle;
use PAGEmachine\Searchable\DataCollector\TCA\DataProvider\TcaInlineCopyToDbRecord;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaInline;
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
        unset($dataProvider[TcaSelectItems::class]);
        $dataProvider[TcaSelectRelations::class] = [
            'depends' => [],
            'before' => [
                TcaInputPlaceholders::class,
                TcaSelectTreeItems::class,
            ],
        ];


        //Unset DataProvider which determines the items to process. This information will be handed over in the compiler input array
        unset($dataProvider[TcaColumnsProcessShowitem::class]);

        //unset record title provider - no need
        unset($dataProvider[TcaColumnsProcessRecordTitle::class]);

        //Add custom inline provider to copy children to database record
        $dataProvider[TcaInlineCopyToDbRecord::class] = [
            'depends' => [
                TcaInline::class,
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

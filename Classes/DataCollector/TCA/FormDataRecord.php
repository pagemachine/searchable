<?php
namespace PAGEmachine\Searchable\DataCollector\TCA;

use TYPO3\CMS\Backend\Form\Exception\DatabaseDefaultLanguageException;
use TYPO3\CMS\Backend\Form\Exception\DatabaseRecordException;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Class for fetching TCA-based data according to the given config
 */
class FormDataRecord implements SingletonInterface
{
    /**
     * SearchableRecordGroup group (backend/form)
     *
     * @var SearchableRecordGroup
     */
    protected $formDataGroup;

    /**
     *
     * @var FormDataCompiler
     */
    protected $formDataCompiler;

    /**
     *
     * @param SearchableRecordGroup|null
     * @param FormDataCompiler|null
     */
    public function __construct(SearchableRecordGroup $formDataGroup = null, FormDataCompiler $formDataCompiler = null)
    {
        $this->formDataGroup = $formDataGroup ?: GeneralUtility::makeInstance(SearchableRecordGroup::class);
        $this->formDataCompiler = $formDataCompiler ?: GeneralUtility::makeInstance(FormDataCompiler::class, $this->formDataGroup);
    }

    /**
     *
     * @return FormDataRecord
     */
    public static function getInstance()
    {
        return GeneralUtility::makeInstance(self::class);
    }

    /**
     * Fetches a single record
     *
     * @param int $uid
     * @param string $table
     * @return array
     */
    public function getRecord($uid, $table, $fieldlist)
    {
        $formDataCompilerInput = [
            'tableName' => $table,
            'vanillaUid' => (int)$uid,
            'command' => 'edit',
            'columnsToProcess' => $fieldlist,
        ];

        try {
            $data = $this->formDataCompiler->compile($formDataCompilerInput);
        //Be nice and catch all errors related to inconsistent data (sometimes strange things happen with extbase relations)
        } catch (DatabaseRecordException $e) {
            $data = [];
        // Catch errors if translation parent is not found
        } catch (DatabaseDefaultLanguageException $e) {
            $data = [];
        }
        return $data;
    }
}

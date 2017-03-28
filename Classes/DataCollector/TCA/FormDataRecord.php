<?php
namespace PAGEmachine\Searchable\DataCollector\TCA;

use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Class for fetching TCA-based data according to the given config
 */
class FormDataRecord implements SingletonInterface {

    /**
     * TcaDatabaseRecord group (backend/form)
     *
     * @var TcaDatabaseRecord
     */
    protected $formDataGroup;

    /**
     *
     * @var FormDataCompiler
     */
    protected $formDataCompiler;

    /**
     *
     * @param TcaDatabaseRecord|null
     * @param FormDataCompiler|null
     */
    public function __construct(SearchableRecordGroup $formDataGroup = null, FormDataCompiler $formDataCompiler = null) {

        $this->formDataGroup = $formDataGroup ?: GeneralUtility::makeInstance(SearchableRecordGroup::class);
        $this->formDataCompiler = $formDataCompiler ?: GeneralUtility::makeInstance(FormDataCompiler::class, $this->formDataGroup);
    }

    /**
     *
     * @return FormDataRecord
     */
    public static function getInstance() {

        return GeneralUtility::makeInstance(self::class);
    }

    /**
     * Fetches a single record
     * 
     * @param integer $uid
     * @param string $table
     * @return array
     */
    public function getRecord($uid, $table) {

        $formDataCompilerInput = [
            'tableName' => $table,
            'vanillaUid' => (int)$uid,
            'command' => 'edit'
        ];

        $data = $this->formDataCompiler->compile($formDataCompilerInput);

        \TYPO3\CMS\Extbase\Utility\DebuggerUtility::var_dump($data, __METHOD__, 8, defined('TYPO3_cliMode'));
        die();

        return $data;


    }

}

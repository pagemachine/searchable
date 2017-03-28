<?php
namespace PAGEmachine\Searchable\DataCollector;

use PAGEmachine\Searchable\DataCollector\RelationResolver\ResolverManager;
use PAGEmachine\Searchable\DataCollector\TCA\FormDataRecord;
use PAGEmachine\Searchable\DataCollector\TCA\PlainValueProcessor;
use PAGEmachine\Searchable\DataCollector\Utility\OverlayUtility;
use PAGEmachine\Searchable\Search;
use PAGEmachine\Searchable\Utility\BinaryConversionUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Class for fetching TCA-based data according to the given config
 */
class TcaDataCollector extends AbstractDataCollector implements DataCollectorInterface {

    /**
     *
     * @var \TYPO3\CMS\Frontend\Page\PageRepository
     * @inject
     */
    protected $pageRepository;

    /**
     *
     * @var \PAGEmachine\Searchable\DataCollector\RelationResolver\ResolverManager
     * @inject
     */    
    protected $resolverManager;

    /**
     * Holds one resolver for each subtype field
     * @var array
     */
    protected $relationResolvers = [];

    /**
     * @var array
     */
    protected static $defaultConfiguration = [
        'excludeFields' => [
            'tstamp',
            'crdate',
            'cruser_id',
            't3ver_oid',
            't3ver_id',
            't3ver_wsid',
            't3ver_label',
            't3ver_state',
            't3ver_stage',
            't3ver_count',
            't3ver_tstamp',
            't3ver_move_id',
            't3_origuid',
            'editlock',
            'sys_language_uid',
            'l10n_parent',
            'l10n_diffsource',
            'deleted',
            'hidden',
            'starttime',
            'endtime',
            'sorting',
            'fe_group'
        ]
    ];

    /**
     * This function will be called by the ConfigurationManager.
     * It can be used to add default configuration
     *
     * @param array $currentSubconfiguration The subconfiguration at this classes' level. This is the part that can be modified
     * @param array $parentConfiguration
     */
    public static function getDefaultConfiguration($currentSubconfiguration, $parentConfiguration) {

        $defaultConfiguration = static::$defaultConfiguration;

        //If this is a subcollector, try fetching the table name from the parent TCA
        if (!$currentSubconfiguration['table'] && !$defaultConfiguration['table']) {

            if ($parentConfiguration['table'] && $GLOBALS['TCA'][$parentConfiguration['table']]['columns'][$currentSubconfiguration['field']]['config']['foreign_table']) {

                $defaultConfiguration['table'] = $GLOBALS['TCA'][$parentConfiguration['table']]['columns'][$currentSubconfiguration['field']]['config']['foreign_table'];
            } else {

                throw new \Exception("Table must be set for TCA record indexing.", 1487344697);
            }
        }

        return $defaultConfiguration;
    }

    /**
     * Add resolver when fetching subtype collectors
     *
     * @Override
     * @param string                 $field        Fieldname to apply this collector to
     * @param DataCollectorInterface $subCollector
     */
    public function addSubCollector($field, DataCollectorInterface $collector) {

        parent::addSubCollector($field, $collector);
        $this->relationResolvers[$field] = $this->resolverManager->findResolverForRelation($field, $collector, $this);
    }

    /**
     * Stores the processedTca for the current record
     *
     * @var array
     */
    protected $processedTca = [];

    /**
     * @return array
     */
    public function getProcessedTca() {

        return $this->processedTca;
    }
    
    
    /**
     * @return array
     */
    public function getTcaConfiguration() {
      return $GLOBALS['TCA'][$this->config['table']];
    }

    /**
     * Fetches records for indexing
     *
     * @return array
     */
    public function getRecordList() {

        $tca = $this->getTcaConfiguration();

        $recordList = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
            "uid", 
            $this->config['table'], 
            $tca['ctrl']['languageField'] . "=0" . $this->pageRepository->enableFields($this->config['table']) . BackendUtility::deleteClause($this->config['table'])
        );
        
        return $recordList;

    }

    /**
     * Returns translation uid
     *
     * @param  int $identifier the base record uid
     * @param  int $language language to translate to
     * @return int|false
     */
    public function getTranslationUid($identifier, $language) {

        $translation = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
            "uid", 
            $this->config['table'], 
            $GLOBALS['TCA'][$this->config['table']]['ctrl']['languageField'] . '=' . (int)$language . ' AND ' . $GLOBALS['TCA'][$this->config['table']]['ctrl']['transOrigPointerField'] . '=' . (int)$identifier . $this->pageRepository->enableFields($this->config['table']), '', '', '1');

        $translationUid = $translation ? $translation[0] : false;

        return $translationUid;
    }

    /**
     * Checks if a record still exists. This is needed for the update scripts
     *
     * @param  int $identifier
     * @return bool
     */
    public function exists($identifier) {

        $recordCount = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows(
            "uid", 
            $this->config['table'], 
            "uid=" . $identifier . $this->pageRepository->enableFields($this->config['table']) . BackendUtility::deleteClause($this->config['table']));

        if ($recordCount > 0) {

            return true;
        }

        return false;
    }

    /**
     * Fetches a single record
     * 
     * @param integer $identifier
     * @return array
     */
    public function getRecord($identifier) {

        $data = FormDataRecord::getInstance()->getRecord($identifier, $this->config['table']);
        $record = $data['databaseRow'];
        $this->processedTca = $data['processedTca'];

        if ($this->language != 0) {

            $record = $this->languageOverlay($record);
        }

        //Cleanup
        $record = $this->removeExcludedFields($record);
        $record = $this->removeUnusedRelationsAndEmptyValues($record);

        //Plain value filling
        $record = $this->fillPlainValues($record);

        //Fill subtypes at last
        if (!empty($this->config['subCollectors'])) {

            foreach ($this->config['subCollectors'] as $subconfig) {

                $fieldname = $subconfig['config']['field'];

                $resolver = $this->relationResolvers[$fieldname];
                $resolvedField = $resolver->resolveRelation($fieldname, $record, $this->getSubCollectorForField($fieldname), $this);

                $record[$fieldname] = $resolvedField;

            }            
        }

        return $record;
    }

    /**
     *
     * @param  array $record
     * @return array
     */
    protected function languageOverlay($record) {

        $overlayUtility = OverlayUtility::getInstance();

        $translationUid = $this->getTranslationUid($record['uid'], $this->language);

        if ($translationUid) {

            $translationData = FormDataRecord::getInstance()->getRecord($translationUid, $this->config['table']);
            $translationRecord = $translationData['databaseRow'];

            foreach ($record as $key => $field) {

                if ($key == "uid" || $key == "pid") {

                    continue;
                }

                //If the FE overlay differs from the raw base record, replace the field with the translated field in the processed record
                if ($overlayUtility->shouldFieldBeOverlaid($this->config['table'], $key, $translationData['defaultLanguageRow'][$key])) {

                    $record[$key] = $translationRecord[$key];
                }
            }

        }

        return $record;


    }

    /**
     * Removes excluded fields from record
     *
     * @param  array $record
     * @return array $record
     */
    protected function removeExcludedFields($record) {

        $excludeFields = $this->config['excludeFields'];

        if (!empty($excludeFields)) {

            foreach ($excludeFields as $excludeField) {

                if (array_key_exists($excludeField, $record)) {

                    unset($record[$excludeField]);
                }
            }

        }

        return $record;

    }

    /**
     * Removes excluded fields from record
     *
     * @param  array $record
     * @return array $record
     */
    protected function removeUnusedRelationsAndEmptyValues($record) {

        foreach ($record as $key => $field) {

            if (empty($field)) {

                unset($record[$key]);
            }
            else if (in_array(
                $this->processedTca['columns'][$key]['config']['type'],
                ['select', 'group', 'passthrough', 'inline', 'flex']) && empty($this->config['subCollectors'][$key])
            ) {
                unset($record[$key]);

            }

        }

        return $record;
    }

    /**
     * Fills plain values like checkboxes with their labels
     *
     * @param  array $record
     * @return array $record
     */
    protected function fillPlainValues($record) {

        $processedTca = $this->getProcessedTca();
        $plainValueProcessor = PlainValueProcessor::getInstance();

        foreach ($record as $fieldname => $value) {

            switch($processedTca['columns'][$fieldname]['config']['type']) {
                case 'check':
                    $record[$fieldname] = $plainValueProcessor->processCheckboxField($value, $processedTca['columns'][$fieldname]['config']);
                    break;
                case 'radio':
                    $record[$fieldname] = $plainValueProcessor->processRadioField($value, $processedTca['columns'][$fieldname]['config']);
                    break;

            }


        }

        return $record;
    }

}

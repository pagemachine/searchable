<?php
namespace PAGEmachine\Searchable\DataCollector;

use PAGEmachine\Searchable\DataCollector\RelationResolver\ResolverManager;
use PAGEmachine\Searchable\DataCollector\TCA\FormDataRecord;
use PAGEmachine\Searchable\DataCollector\TCA\PlainValueProcessor;
use PAGEmachine\Searchable\DataCollector\Utility\FieldListUtility;
use PAGEmachine\Searchable\DataCollector\Utility\OverlayUtility;
use PAGEmachine\Searchable\Enumeration\TcaType;
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
        'pid' => null,
        //sys_language_overlay setting for this collector. Use 0|1|hideNonTranslated
        'sysLanguageOverlay' => 1,
        'mode' => 'whitelist',
        'fields' => [
        ]
    ];

    /**
     * Field whitelist - filters excludeFields and unused relations/unsupported types. Passed on to the FormEngine to reduce overhead
     *
     * @var array|null
     */
    protected $fieldWhitelist = null;

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

        $column = $collector->getConfig()['field'];
        $this->relationResolvers[$field] = $this->resolverManager->findResolverForRelation($column, $collector, $this);
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
     * Returns true if a subcollector exists for given column (this is the TCA column, not the subtype fieldname!)
     *
     * @param  string $field
     * @return boolean
     */
    public function subCollectorExistsForColumn($column) {

        if (!empty($this->config['subCollectors'])) {

            foreach ($this->config['subCollectors'] as $subconfig) {

                if ($subconfig['config']['field'] == $column) {

                    return true;
                }

            }            
        }

        return false;
    }
    
    
    /**
     * @return array
     */
    public function getTcaConfiguration() {
      return $GLOBALS['TCA'][$this->config['table']];
    }

    /**
     * returns records
     *
     * @return \Generator
     */
    public function getRecords()
    {
        $tca = $this->getTcaConfiguration();

        $pidRestriction = $this->config['pid'] !== null ? ' AND pid = ' . $this->config['pid'] : '';

        $dbQuery = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
            "uid", 
            $this->config['table'], 
            $tca['ctrl']['languageField'] . ' IN' . "(0,-1)" . $pidRestriction . $this->pageRepository->enableFields($this->config['table']) . BackendUtility::deleteClause($this->config['table'])
        );
        
        while ($rawRecord = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbQuery)) {

            yield $this->getRecord($rawRecord['uid']);
        }
    }

    /**
     * Works like getRecords, but processes only a given uid list to update
     *
     * @param  array $updateUidList
     * @return \Generator
     */
    public function getUpdatedRecords($updateUidList) {

        $tca = $this->getTcaConfiguration();

        $pidRestriction = $this->config['pid'] != null ? ' AND pid = ' . $this->config['pid'] : '';

        foreach ($updateUidList as $uid) {

            $record = $GLOBALS['TYPO3_DB']->exec_SELECTgetSingleRow(
                "uid", 
                $this->config['table'], 
                "uid=" . $uid . $pidRestriction . $this->pageRepository->enableFields($this->config['table']) . BackendUtility::deleteClause($this->config['table'])
            );

            if ($record) {

                $fullRecord = $this->getRecord($record['uid']);

                if (!empty($fullRecord)) {

                    yield $fullRecord;
                    continue;
                }
            }

            yield ['uid' => $uid, 'deleted' => 1];

        }

    }

    /**
     * Fetches a single record
     * 
     * @param integer $identifier
     * @return array
     */
    public function getRecord($identifier) {

        $data = FormDataRecord::getInstance()->getRecord($identifier, $this->config['table'], $this->getFieldWhitelist());

        if (empty($data)) {

            return [];
        }

        $record = $data['databaseRow'];
        $this->processedTca = $data['processedTca'];

        $record = $this->languageOverlay($record);

        if (!empty($record)) {
            //Cleanup
            $record = $this->processColumns($record);
        }

        return $record;
    }

    /**
     * Get overlay
     *
     * @param  array $record
     * @return array
     */
    protected function languageoverlay($record) {

        return OverlayUtility::getInstance()->languageOverlay($this->config['table'], $record, $this->language, $this->getFieldWhitelist(), $this->config['sysLanguageOverlay']);
    }

    /**
     * Processes each column depending on type
     *
     * @param  array $record
     * @return array
     */
    protected function processColumns($record)
    {
        $plainValueProcessor = PlainValueProcessor::getInstance();

        //Preprocess fields
        foreach ($record as $key => $field) {

            if (empty($field) || (!in_array($key, $this->getFieldWhitelist()) && !$this->subCollectorExistsForColumn($key)))
            {
                unset($record[$key]);
                continue;
            }

            $type = $this->processedTca['columns'][$key]['config']['type'];

            //plain types
            switch ($type) 
            {
                case TcaType::RADIO:
                    $record[$key] = $plainValueProcessor->processRadioField($field, $this->processedTca['columns'][$key]['config']);
                    break;

                case TcaType::CHECK:
                    $record[$key] = $plainValueProcessor->processCheckboxField($field, $this->processedTca['columns'][$key]['config']);
                    break;
            }
        }

        //Fill subtypes at last
        if (!empty($this->config['subCollectors'])) {

            foreach ($this->config['subCollectors'] as $key => $subconfig) {

                $fieldname = $subconfig['config']['field'];

                $resolver = $this->relationResolvers[$key];
                $resolvedField = $resolver->resolveRelation($fieldname, $record, $this->getSubCollectorForField($key), $this);

                //Unset the original column
                unset($record[$fieldname]);

                //Add processed column
                $record[$key] = $resolvedField;

            }            
        }

        //Cleanup - remove empty fields
        $record = array_filter($record);

        return $record;

    }

    /**
     * Returns the field whitelist for this record
     *
     * @return array
     */
    public function getFieldWhitelist() {

        if ($this->fieldWhitelist == null) {


            $this->fieldWhitelist = FieldListUtility::getInstance()->createFieldList($this->config['fields'], $this->getTcaConfiguration(), $this->config['mode']);

        }
        return $this->fieldWhitelist;
    }

}

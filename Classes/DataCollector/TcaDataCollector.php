<?php
namespace PAGEmachine\Searchable\DataCollector;

use PAGEmachine\Searchable\DataCollector\RelationResolver\ResolverManager;
use PAGEmachine\Searchable\DataCollector\TCA\FormDataRecord;
use PAGEmachine\Searchable\DataCollector\TCA\PlainValueProcessor;
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
            $tca['ctrl']['languageField'] . "=0" . $pidRestriction . $this->pageRepository->enableFields($this->config['table']) . BackendUtility::deleteClause($this->config['table'])
        );
        
        while ($rawRecord = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($dbQuery)) {

            yield $this->getRecord($rawRecord['uid']);
        }
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

        $pidRestriction = $this->config['pid'] !== null ? ' AND pid = ' . $this->config['pid'] : '';

        $recordCount = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows(
            "uid", 
            $this->config['table'], 
            "uid=" . $identifier . $pidRestriction . $this->pageRepository->enableFields($this->config['table']) . BackendUtility::deleteClause($this->config['table']));

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

        $data = FormDataRecord::getInstance()->getRecord($identifier, $this->config['table'], $this->getFieldWhitelist());
        $record = $data['databaseRow'];
        $this->processedTca = $data['processedTca'];


        if ($this->language != 0) {

            $record = $this->languageOverlay($record);
        }

        //Cleanup
        $record = $this->processColumns($record);

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

            $translationData = FormDataRecord::getInstance()->getRecord($translationUid, $this->config['table'], $this->getFieldWhitelist());
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

            if (empty($field) || $this->isExcludeField($key))
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

        return $record;

    }

    /**
     * Returns the field whitelist for this record
     *
     * @return array
     */
    public function getFieldWhitelist() {

        if ($this->fieldWhitelist == null) {

            $whitelist = $this->getWhitelistSystemFields();

            $tca = $this->getTcaConfiguration();

            foreach ($tca['columns'] as $key => $column) {

                $type = $column['config']['type'];

                if (
                    !$this->isExcludeField($key) && //excluded
                    (TcaType::isPlain($type) || (TcaType::isRelation($type) && $this->subCollectorExistsForColumn($key))) //Plain type or relation with subcollector
                    )
                {
                    $whitelist[] = $key;
                }

            }

            $this->fieldWhitelist = $whitelist;

        }
        return $this->fieldWhitelist;
    }

    /**
     * Returns the whitelisted system fields (always enabled)
     *
     * @return array
     */
    protected function getWhitelistSystemFields() {

        $systemFields = [
            'uid',
            'pid'
        ];

        return $systemFields;
    }

    /**
     * Returns true if this field should be excluded
     *
     * @param  string  $fieldname
     * @return boolean
     */
    protected function isExcludeField($fieldname) {

        if (!empty($this->config['excludeFields']) && in_array($fieldname, $this->config['excludeFields'])) {

            return true;
        }

        return false;
    }

}

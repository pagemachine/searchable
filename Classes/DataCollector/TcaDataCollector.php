<?php
namespace PAGEmachine\Searchable\DataCollector;

use PAGEmachine\Searchable\DataCollector\TCA\RelationResolver\ResolverManager;
use PAGEmachine\Searchable\DataCollector\TCA\FormDataRecord;
use TYPO3\CMS\Backend\Utility\BackendUtility;

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
     * @var \PAGEmachine\Searchable\DataCollector\TCA\RelationResolver\ResolverManager
     * @inject
     */    
    protected $resolverManager;

    protected $defaultConfiguration = [
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
     * The table this collector relates to
     * @var string $table
     */
    protected $table;
    
    /**
     * @return string
     */
    public function getTable() {
      return $this->table;
    }
    
    /**
     * @param string $table
     * @return void
     */
    public function setTable($table) {
      $this->table = $table;
    }
    
    /**
     * @return array
     */
    public function getTcaConfiguration() {
      return $GLOBALS['TCA'][$this->table];
    }

    /**
     * Builds configuration - hook into here if you want to add some stuff to config manually
     *
     * @param  array  $configuration
     * @return array $mergedConfiguration
     * @Override
     */
    public function buildConfiguration($configuration = []) {

        $configuration = parent::buildConfiguration($configuration);
        
        if (!empty($configuration['table'])) {

            $this->table = $configuration['table'];
        } else {

            throw new \Exception("Table must be set for TCA record indexing.", 1487344697);
        }

        return $configuration;

    }

    /**
     * Overriden to assign the correct table to the child (if needed)
     * 
     * @param string $classname
     * @param  array  $collectorConfig
     * @return DataCollectorInterface
     * @Override
     */
    public function buildSubCollector($classname, $collectorConfig = []) {

        $tca = $this->getTcaConfiguration();

        $childTable = $tca['columns'][$collectorConfig['field']]['config']['foreign_table'];
        $collectorConfig['table'] = $childTable;

        $subCollector = parent::buildSubCollector($classname, $collectorConfig);

        return $subCollector;

    }

    /**
     * Fetches records for indexing
     *
     * @return array
     */
    public function getRecordList() {

        $recordList = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
            "uid", 
            $this->table, 
            "1=1" . $this->pageRepository->enableFields($this->table) . BackendUtility::deleteClause($this->table)
        );
        
        return $recordList;

    }

    /**
     * Fetches a single record
     * 
     * @param integer $identifier
     * @return array
     */
    public function getRecord($identifier) {

        $record = FormDataRecord::getInstance()->getRecord($identifier, $this->table);

        //Cleanup
        $record = $this->removeExcludedFields($record);
        $record = $this->removeUnusedRelationsAndEmptyValues($record);

        $tca = $this->getTcaConfiguration();

        if (!empty($this->configuration['subtypes'])) {

            foreach ($this->configuration['subtypes'] as $subconfig) {
                $fieldname = $subconfig['config']['field'];

                $record[$fieldname] = $this->fetchSubtypeField(
                    $fieldname,
                    $record[$fieldname], 
                    $tca['columns'][$fieldname]
                );

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

        $excludeFields = $this->configuration['excludeFields'];

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
                $GLOBALS['TCA'][$this->configuration['table']]['columns'][$key]['config']['type'],
                ['select', 'group', 'passthrough', 'inline', 'flex']) && empty($this->configuration['subtypes'][$key])
            ) {
                unset($record[$key]);

            }

        }

        return $record;
    }

    /**
     * Fetches a subtype relation field (select, group etc.)
     *
     * @param string $fieldname
     * @param  mixed $rawField The field as it is returned from the Formengine. Contains the necessary uids
     * @param  array $fieldTca
     * @return array
     */
    protected function fetchSubtypeField($fieldname, $rawField, $fieldTca) {

        $resolver = $this->resolverManager->getResolverForRelation($fieldTca['config']['type']);

        $resolvedField = $resolver->resolveRelation($rawField, $fieldTca, $this->getSubCollectorForField($fieldname));

        return $resolvedField;
    }








}

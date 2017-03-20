<?php
namespace PAGEmachine\Searchable\Query;


/*
 * This file is part of the PAGEmachine Searchable project.
 */

/**
 * Query class to store update information
 * This is NOT the query for updating records, use a BulkQuery instead!
 */
class UpdateQuery extends AbstractQuery {

    /**
     * Update index
     * 
     * @var string
     */
    protected $index;

    /**
     * @return string
     */
    public function getIndex() {
      return $this->index;
    }

    /**
     * Update type
     * 
     * @var string
     */
    protected $type = "updates";

    /**
     * @return string
     */
    public function getType() {
      return $this->type;
    }

    /**
     * @return void
     */
    public function __construct() {

        parent::__construct();
        $this->index = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['searchable']['updateIndex']['name'];

        $this->init();
    }

    /**
     * Creates the basic information for bulk indexing
     * @return void
     */
    public function init() {
        $this->parameters =  [
            'index' => $this->getIndex(),
            'type' => $this->getType(),
            'body' => [
                'table' => null,
                'where' => null
            ]
        ];
    }

    public function setBody($body) {

        $this->parameters['body'] = $body;
        return $this;
    }

    public function setTable($table) {

        $this->parameters['body']['table'] = $table;
        return $this;
    }

    public function setWhere($where) {

        $this->parameters['body']['where'] = $where;
        return $this;
    }

    /**
     * Executes a bulk insertion query
     * 
     * @return array
     */
    public function execute() {

        // Use id field to store identical updates only once
        $id = sha1($this->parameters['body']['table'] . "/" . $this->parameters['body']['where']);
        $this->parameters['id'] = $id;


        /**
         * @var array
         */
        $response = $this->client->index($this->getParameters());

        return $response;
    }





    

}

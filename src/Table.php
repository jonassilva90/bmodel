<?php

namespace Bmodel;

class Table extends QueryBuilder
{
    protected $connectionId;
    protected $table;
    protected $tableAlias;
    protected $primaryKey = 'id';
    private $fields = [];
    private $relations = [];
    public static $fieldsGlobal = [];
    public function __construct()
    {
        $this->setTableName($this->table, $this->tableAlias);
        $this->setPrimaryKey($this->primaryKey);
        $this->setConnectionId($this->connectionId);
        parent::__construct();
    }
    public function setConf($tableName = null, $tableAlias = null, $primaryKey = null, $connId = null)
    {
        $this->setConnectionId($connId);
        $this->setTableName($tableName, $tableAlias);
        $this->setPrimaryKey($primaryKey);
        $this->defineFields();
        $this->defineRelations();
    }
    public function getTable()
    {
        return $this->getTableName();
    }
    public function getFields()
    {
        return $this->fields;
    }
    public function getConn()
    {
        return Connection::connect($this->getConnectionId());
    }
    public function defineFields()
    {
        $this->fields = Query::getFieldsFromDB($this->getTableName(), 0, $this->getConnectionId());
    }
    public function defineRelations()
    {
    }
    public function addRelation($table, $alias, $fieldTable, $fieldRelation, $primaryKey = null, $connId = null)
    {
        $this->relations[$alias] = (object)[
            'table' => $table,
            'alias' => $alias,
            'fieldTable' => $fieldTable,
            'fieldRelation' => $fieldRelation,
            'primaryKey' => $primaryKey,
            'connId' => $connId,
            'value' => null
        ];
    }
    public function addRel($table, $alias, $fieldTable, $fieldRelation, $primaryKey = null, $connId = null)
    {
        $this->addRelation($table, $alias, $fieldTable, $fieldRelation, $primaryKey, $connId);
    }
}

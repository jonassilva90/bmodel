<?php

namespace Bmodel;

class Table
{
    private $connectionId = 0;
    private $table;
    private $tableAlias;
    private $primaryKey = 'id';
    private $fields = [];
    private $relations = [];
    public function setConf($tableName = null, $tableAlias = null, $primaryKey = null, int $connId = 0)
    {
        $this->setConnectionId($connId);
        $this->setTableName($tableName, $tableAlias);
        $this->setPrimaryKey($primaryKey);
        $this->defineFields();
        $this->defineRelations();
    }

    public function setConnectionId(int $connId)
    {
        $this->connectionId = $connId;
        return $this;
    }

    public function getConnectionId(): int
    {
        return $this->connectionId;
    }

    public function setPrimaryKey(string $name)
    {
        $this->primaryKey = $name;
        return $this;
    }

    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }

    public function setTableName($table, $tableAlias = null)
    {
        $this->table = Commons::snakeCase($table);
        $this->tableAlias = $tableAlias;
    }

    public function getTableName()
    {
        return $this->table;
    }

    public function getTableAlias()
    {
        return $this->tableAlias;
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
        // $this->fields = Query::getFieldsFromDB($this->getTableName(), 0, $this->getConnectionId());
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

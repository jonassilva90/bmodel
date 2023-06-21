<?php

namespace Bmodel;

class Model extends Record
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

    public function defineRelations()
    {
    }
}

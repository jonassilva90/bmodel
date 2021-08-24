<?php

namespace Bmodel;

class Table
{
    public static $connectionId;
    public static $tableName;
    public static $primaryKey = 'id';
    public static $fields = [];
    public static $relations = [];
    /**
     * @var QueryBuilder 
     */
    private $queryBuild;
    public static $fieldsGlobal = [];
    public function __construct($tableName = '')
    {
        if ($tableName != '') {
            self::$tableName = Commons::snakeCase($tableName);
        }

        $this->defineConnection();
        $this->defineTable();
        $this->defineFields();
        $this->defineRelations();
    }

    public static function setPrimaryKey($name)
    {
        self::$primaryKey = $name;
    }

    public static function getPrimaryKey()
    {
        return self::$primaryKey;
    }

    public static function createPseudo($tableName)
    {
        // Criando um pseudo class para a table (quando nao existir o Table)
        /*
        $table = new class($table) extends Table {
            static $tableName;
            public function setTable  ($table) {
                $this->tableName = Commons::snakeCase($table);
                $this->fields = $this->getFieldsFromDB(0);
            }
        };
        $table->setTable($tableName);
        */
        $table = new static($tableName);

        return $table;
    }
    public function defineConnection()
    {
        self::$connectionId = null;
    }
    public function defineTable($tableName = '')
    {
        if ($tableName != '') {
            self::$tableName = $tableName;
        }
    }
    public function defineFields()
    {
        self::$fields = $this->getFieldsFromDB();
    }
    public function defineRelations()
    {
    }

    public static function getConnection()
    {
        return Connection::connect(self::$connectionId);
    }

    /**
     * Pega lista de campos no banco de dados
     *
     * @param integer $type Tipo de retorno, 0 para array de Bmodel\Field / 1 para array de string
     *
     * @throws \Exception
     * @return Array
     * @author Jonas Ribeiro <jonasribeiro19@gmail.com>
     * @version 1.0
     */
    public function getFieldsFromDB($type = 0)
    {
        $table = self::$tableName;
        $connectionsId = self::$connectionId;
        $con = is_null(self::$connectionId) ? 0 : self::$connectionId;
        if (!isset(Table::$fieldsGlobal[$con][$table])) {
            if (is_null($table) || !Connection::isTable($table, $connectionsId)) {
                throw new \Exception("Table '{$table}' not exists");
            }
            //-------------------------------------------------//
            $result = Query::query("SELECT * FROM `{$table}` WHERE 0 LIMIT 1", null, $connectionsId);
            $c = $result->columnCount();
            $fields = [];

            for ($i = 0; $i < $c; $i++) {
                $f = $result->getColumnMeta($i);
                $fields[$f['name']] = new Field($f);
            }
            Table::$fieldsGlobal[$con][$table] = $fields;
        }

        if ($type == 1) {
            $fields = [];
            foreach (Table::$fieldsGlobal[$con][$table] as $Field) {
                $fields[] = $Field->getName();
            }
            return $fields;
        }

        return Table::$fieldsGlobal[$con][$table];
    }

    /**
     * Pegar obj Table da tabela
     *
     * @param String $table Nome da tabela no formato PascalCase ou snakeCase
     * @param String $alias Alias da tabela
     * @param String $primaryKey
     *
     * @return void
     * @author Jonas Ribeiro <jonasribeiro19@gmail.com>
     * @version 1.0
     */
    public static function getTable($table, $alias = null, $primaryKey = 'id')
    {
        if (is_null($alias)) {
            $alias = Commons::snakeCase($table);
        }

        $table = Connection::getTable($table);
        $table->setPrimaryKey($primaryKey);

        if (!$table) {
            // Criando um pseudo class para a table (quando nao existir o Table)
            $table = self::createPseudo($table);
        }

        return $table;
    }

    public static function create()
    {
        $model = Connection::getModel(self::$tableName, self::$primaryKey);
        if (!$model) {
            $model = Record::createPseudo(self::$tableName, null, self::$primaryKey);
        }
        return $model;
    }

    public function getBuild()
    {
        if (is_null($this->queryBuild)) {
            $this->queryBuild = new QueryBuilder();
        }
        $this->queryBuild->setTableName(self::$tableName);
        $this->queryBuild->setPrimaryKey(self::$primaryKey);
        return $this;
    }

    public function select($fields = null)
    {
        $this->getBuild();
        $this->queryBuild->select($fields);
        return $this;
    }

    public function innerJoin($table, $on, $name)
    {
        $this->getBuild();
        $this->queryBuild->innerJoin($table, $on, $name);
        return $this;
    }

    public function leftJoin($table, $on, $name)
    {
        $this->getBuild();
        $this->queryBuild->leftJoin($table, $on, $name);
        return $this;
    }

    public function rightJoin($table, $on, $name)
    {
        $this->getBuild();
        $this->queryBuild->rightJoin($table, $on, $name);
        return $this;
    }

    public function where($where, $params = [])
    {
        $this->getBuild();
        $this->queryBuild->where($where, $params);
        return $this;
    }

    public function andWhere($where, $params = [])
    {
        $this->getBuild();
        $this->queryBuild->andWhere($where, $params);
        return $this;
    }

    public function orderBy($order)
    {
        $this->getBuild();
        $this->queryBuild->orderBy($order);
        return $this;
    }

    public function start($start)
    {
        $this->getBuild();
        $this->queryBuild->start($start);
        return $this;
    }

    public function limit($limit)
    {
        $this->getBuild();
        $this->queryBuild->limit($limit);
        return $this;
    }

    public function count()
    {
        return $this->getBuild()->count();
    }

    public function insert($values)
    {
        return $this->getBuild()->insert($values);
    }

    public function update($values, $id = null)
    {
        return $this->getBuild()->update($values, $id);
    }

    public function delete()
    {
        return $this->getBuild()->delete();
    }

    /**
     * Traz um registro por id
     *
     * @param String|Int $id Id dao registro
     *
     * @return Record|Boolean False de nao encontrar
     * @author Jonas Ribeiro <jonasribeiro19@gmail.com>
     * @version 1.0
     */
    public function find($id = null)
    {
        return $this->getBuild()->find($id);
    }

    /**
     * Traz um registro por parametro
     *
     * @param Array|String $params Array com os valores ou um Where(String)
     * Exemplo: ['active' => 1] ou 'active = 1'
     *
     * @return Record|Boolean False de nao encontrar
     * @author Jonas Ribeiro <jonasribeiro19@gmail.com>
     * @version 1.0
     */
    public function findBy($params)
    {
        return $this->getBuild()->findBy($params);
    }

    /**
     * Traz um registro e Apaga
     *
     * @param String|Int $id Id dao registro
     *
     * @return Record|Boolean False de nao encontrar ou não apagar
     * @author Jonas Ribeiro <jonasribeiro19@gmail.com>
     * @version 1.0
     */
    public function findDelete($id)
    {
        return $this->getBuild()->findDelete($id);
    }

    public function get()
    {
        return $this->getBuild()->getAll();
    }

    public function getAll($type = 0)
    {
        return $this->getBuild()->getAll($type);
    }
}

<?php

namespace Bmodel;

class Record
{
    private $table;
    private $primaryKey = 'id';
    private $_data;
    private $paramsSet = [];
    public function __construct()
    {
        $data = [];
    }
    public static function createPseudo($tableName, $data = null, $primaryKey = 'id')
    {
        // Criando um pseudo class para a table (quando nao existir o Table)
        $record = new class () extends Record
        {
        };
        $record->setTableName($tableName);
        $record->setPrimaryKey($primaryKey);
        if (!is_null($data)) {
            $record->setData($data);
        }
        $record->reviewFields($data);

        return $record;
    }
    public function setPrimaryKey($name)
    {
        $this->primaryKey = $name;
    }
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }
    public function reviewFields()
    {
        $data = $this->_data;
        $table = Query::getTable($this->table, null);
        $this->primaryKey = $table->getPrimaryKey();
        $fields = $table->getFieldsFromDB();
        foreach ($fields as $field => $objField) {
            if (is_null($data) || !array_key_exists($field, $data)) {
                $data[$field] = $objField->getDefault();
            }
        }
        $this->setData($data);
    }
    public function setData($data = [])
    {
        $this->_data = $data;
    }

    public function __set($name, $value = null)
    {
        $this->_data[$name] = $value;
        $this->paramsSet[$name] = $value;
    }

    public function __get($name)
    {
        if (!array_key_exists($name, $this->_data)) {
            throw new \Exception("Campo '{$name}' não existe na tabela '$this->table'");
        }
        return $this->_data[$name];
    }

    public function getFields()
    {
        return $this->_data;
    }

    public function setTableName($table)
    {
        $this->table = $table;
    }

    public function save()
    {
        $primaryKey = $this->primaryKey;
        $table = Query::getTable($this->table, null, $primaryKey);
        $this->_data[$primaryKey] = $this->_data[$primaryKey] ?? null;
        if (is_null($this->_data[$primaryKey])) {
            $dados = array_filter(
                $this->_data,
                function ($k) use ($primaryKey){
                    return $k != $primaryKey;
                },
                ARRAY_FILTER_USE_KEY
            );
            $primaryKeyValue =$table->insert($dados);
            $result = !!$primaryKeyValue;
            if ($result) {
                $this->_data[$primaryKey] = $primaryKeyValue;
            }
        } else {
            $result = $table->update($this->paramsSet, $this->_data[$primaryKey]);
        }

        // Refresh data
        $this->_data = Query::getTable($this->table, null, $primaryKey)->find($this->_data[$primaryKey])->toArray();

        return $result;
    }

    public function delete()
    {
        $primaryKey = $this->primaryKey;
        $this->_data[$primaryKey] = $this->_data[$primaryKey] ?? null;
        if (is_null($this->_data[$primaryKey])) {
            return false;
        }
        return Query::getTable($this->table, null, $primaryKey)
            ->findDelete($this->_data[$primaryKey]);
    }

    public function toArray()
    {
        return $this->_data;
    }

    public function toArrayNum()
    {
        return array_values($this->_data);
    }

    public function toJSON($typeJSON = 0)
    {
        return json_encode($this->_data, $typeJSON);
    }
}

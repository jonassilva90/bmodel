<?php

namespace Bmodel;

class Record
{
    private $table;
    private $connId;
    private $primaryKey = 'id';
    private $data = [];
    private $paramsSet = [];
    public function setPrimaryKey($name)
    {
        $this->primaryKey = $name;
    }
    public function getPrimaryKey()
    {
        return $this->primaryKey;
    }
    public function setTableName($tableName)
    {
        $this->table = $tableName;
    }
    public function getTableName()
    {
        return $this->table;
    }
    public function setConnectionId($connId)
    {
        $this->connId = $connId;
    }
    public function getConnectionId()
    {
        return $this->connId;
    }
    public function setData($data = [])
    {
        $this->data = $data;
    }

    public function __set($name, $value = null)
    {
        $this->data[$name] = $value;
        $this->paramsSet[] = $name;
    }

    public function __get($name)
    {
        if (!array_key_exists($name, $this->data)) {
            throw new \Exception("Campo '{$name}' não existe na tabela '$this->table'");
        }
        return $this->data[$name];
    }

    private function getQueryBuilder(): QueryBuilder
    {
        $q = new QueryBuilder();
        $q->setPrimaryKey($this->getPrimaryKey());
        $q->setTableName($this->getTableName());
        $q->setConnectionId($this->getConnectionId());
        return $q;
    }

    public function save()
    {
        $q = $this->getQueryBuilder();

        $primaryKey = $this->primaryKey;
        $primaryKeyValue = $this->data[$primaryKey] ?? null;
        if (is_null($primaryKeyValue)) {
            $data = array_filter(
                $this->data,
                function ($k) use ($primaryKey) {
                    return $k != $primaryKey;
                },
                ARRAY_FILTER_USE_KEY
            );
            $primaryKeyValue = $q->insert($data);
            $result = !!$primaryKeyValue;
            if ($result) {
                $this->data[$primaryKey] = $primaryKeyValue;
            }
            $this->data = $q->find($primaryKeyValue)->toArray();
            return $result;
        }

        $data = [];
        foreach ($this->paramsSet as $fieldName) {
            if ($fieldName != $primaryKey) {
                continue;
            }

            if (isset($this->data[$fieldName])) {
                $data[$fieldName] = $this->data[$fieldName];
            }
        }

        return $q->update($data, $primaryKeyValue);
    }

    public function delete(): bool
    {
        $primaryKey = $this->primaryKey;
        if (!isset($this->data[$primaryKey]) || is_null($this->data[$primaryKey])) {
            return false;
        }
        return $this->getQueryBuilder()->delete($this->data[$primaryKey]);
    }

    public function toArray()
    {
        return $this->data;
    }

    public function toArrayNum()
    {
        return array_values($this->data);
    }

    public function toJSON($typeJSON = 0)
    {
        return json_encode($this->data, $typeJSON);
    }
}

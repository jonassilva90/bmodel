<?php

namespace Bmodel;

class ResultsQuery
{
    private $data = [];
    private $c = -1;
    private $querySql;
    private $params = [];
    private $primaryKey = 'id';
    public function __construct($data, $querySql = '', $params = [], $primaryKey = 'id')
    {
        $this->querySql = $querySql;
        $this->data = $data;
        $this->params = $params;
    }

    public function __set($name, $value = null)
    {
        $this->data[$name] = $value;
        $this->_paramsSet[$name] = $value;
    }

    public function __get($name)
    {
        if (!array_key_exists($name, $this->data)) {
            throw new \Exception("Campo '{$name}' não existe na tabela '$this->_table}'");
        }
        return $this->data[$name];
    }

    public function getQuerySql()
    {
        return $this->querySql;
    }

    public function getParams()
    {
        return $this->params;
    }

    public function count()
    {
        return count($this->data[$this->c]);
    }

    public function fetch()
    {
        $this->c++;
        if (isset($this->data[$this->c])) {
            $this->data[$this->c]->setPrimaryKey($this->primaryKey);
            return $this->data[$this->c];
        }
        return false;
    }

    public function fetchAll()
    {
        $primaryKey = $this->primaryKey;

        return array_map(
            function ($row) use ($primaryKey) {
                $row->setPrimaryKey($primaryKey);
                return $row;
            },
            $this->data
        );
    }

    public function toArray()
    {
        return array_map(function ($v) {
            return $v->toArray();
        }, $this->data);
    }

    public function toArrayNum()
    {
        return array_map(function ($v) {
            return $v->toArrayNum();
        }, $this->data);
    }

    public function toJSON($typeJSON = 0)
    {
        return json_encode($this->toArray(), $typeJSON);
    }
}

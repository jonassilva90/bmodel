<?php

namespace Bmodel;

class Record extends QueryBuilder
{
    private $data = [];
    private $paramsSet = [];
    public function setData($data = [], $setParamsUpdate = false)
    {
        $this->data = $data;
        $this->paramsSet = [];
        if ($setParamsUpdate) {
            foreach ($data as $field => $value) {
                $this->paramsSet[] = $field;
            }
        }
    }

    public function __set($name, $value = null)
    {
        $this->data[$name] = $value;
        $this->paramsSet[] = $name;
    }

    public function __get($name)
    {
        if (!array_key_exists($name, $this->data)) {
            throw new \Exception("Campo '{$name}' não existe na tabela '" . $this->getTableName() . "'");
        }
        return $this->data[$name];
    }

    public function __isset($name)
    {
        if (isset($this->data[$name])) {
            return (false === empty($this->data[$name]));
        } else {
            return null;
        }
    }

    public function save()
    {

        $primaryKey = $this->getPrimaryKey();
        $primaryKeyValue = $this->data[$primaryKey] ?? null;
        if (is_null($primaryKeyValue)) {
            $data = array_filter(
                $this->data,
                function ($k) use ($primaryKey) {
                    return $k != $primaryKey;
                },
                ARRAY_FILTER_USE_KEY
            );
            $primaryKeyValue = $this->insert($data);
            $result = !!$primaryKeyValue;
            if ($result) {
                $this->data[$primaryKey] = $primaryKeyValue;
            }
            // $this->data = $q->find($primaryKeyValue)->toArray();
            return $primaryKeyValue;
        }

        $data = [];
        foreach ($this->paramsSet as $fieldName) {
            if ($fieldName == $primaryKey) {
                continue;
            }

            if (isset($this->data[$fieldName])) {
                $data[$fieldName] = $this->data[$fieldName];
            }
        }

        return $this->update($data, $primaryKeyValue);
    }

    public function deleteRecord(): bool
    {
        $primaryKey = $this->getPrimaryKey();
        if (!isset($this->data[$primaryKey]) || is_null($this->data[$primaryKey])) {
            return false;
        }
        return $this->delete($this->data[$primaryKey]);
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

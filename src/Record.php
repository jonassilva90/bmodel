<?php

namespace Bmodel;

class Record
{
    private $table;
    private $data;
    private $paramsSet = [];
    public function __construct()
    {
        $data = [
            'id' => null
        ];
    }
    public static function createPseudo($tableName, $data = null)
    {
        // Criando um pseudo class para a table (quando nao existir o Table)
        $record = new class () extends Record
        {
        };
        $record->setTableName($tableName);
        if (!is_null($data)) {
            $record->setData($data);
        }
        $record->reviewFields($data);

        return $record;
    }
    public function reviewFields()
    {
        $data = $this->data;
        $fields = Query::getTable($this->table)->getFieldsFromDB();
        foreach ($fields as $field => $objField) {
            if (is_null($data) || !array_key_exists($field, $data)) {
                $data[$field] = $objField->getDefault();
            }
        }
        $this->setData($data);
    }
    public function setData($data = [])
    {
        $this->data = $data;
    }

    public function __set($name, $value = null)
    {
        $this->data[$name] = $value;
        $this->paramsSet[$name] = $value;
    }

    public function __get($name)
    {
        if (!array_key_exists($name, $this->data)) {
            throw new \Exception("Campo '{$name}' não existe na tabela '$this->table}'");
        }
        return $this->data[$name];
    }

    public function getFields()
    {
        return $this->data;
    }

    public function setTableName($table)
    {
        $this->table = $table;
    }

    public function save()
    {
        if (is_null($this->data['id'])) {
            $dados = array_filter(
                $this->data,
                function ($k) {
                    return $k != 'id';
                },
                ARRAY_FILTER_USE_KEY
            );
            $result = Query::getTable($this->table)->insert($dados);
            if ($result != false) {
                $this->data['id'] = $result;
                $result = true;
            }
        } else {
            $result = Query::getTable($this->table)->update($this->paramsSet, $this->data['id']);
        }

        // Refresh data
        $find = Query::getTable($this->table)->find($this->data['id']);
        $this->data = $find->toArray();

        return $result;
    }

    public function delete()
    {
        if (is_null($this->data['id'])) {
            return false;
        }
        return Query::getTable($this->table)->findDelete($this->data['id']);
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

<?php namespace Bmodel;

class Record {
    private $_table;
    private $_data;
    private $_paramsSet = [];
    public function __construct ()
    {
        $_data = [
            'id' => null
        ];
    }
    public static function createPseudo ($tableName, $data = null)
    {
        // Criando um pseudo class para a table (quando nao existir o Table)
        $record = new class() extends Record { };
        $record->setTableName($tableName);
        if (!is_null($data)) {
            $record->setData($data);
        }
        $record->reviewFields($data);

        return $record;
    }
    public function reviewFields ()
    {
        $data = $this->_data;
        $fields = Query::getTable($this->_table)->getFieldsFromDB();
        foreach ($fields as $field => $objField) {
            if (is_null($data) || !array_key_exists($field, $data)) {
                $data[$field] = $objField->getDefault();
            }
        }
        $this->setData($data);
    }
    public function setData ($data = [])
    {
        $this->_data = $data;
    }

    public function __set ($name, $value = null)
    {
        $this->_data[$name] = $value;
        $this->_paramsSet[$name] = $value;
    }

    public function __get ($name)
    {
        if (!array_key_exists($name, $this->_data)) {
            throw new \Exception("Campo '{$name}' não existe na tabela '$this->_table}'");
        }
        return $this->_data[$name];
    }

    public function getFields () {
        return $this->_data;
    }

    public function setTableName ($table)
    {
        $this->_table = $table;
    }

    public function save ()
    {
        if (is_null($this->_data['id'])) {
            $dados = array_filter(
                $this->_data,
                function ($k) { return $k != 'id';},
                ARRAY_FILTER_USE_KEY
            );
            $result = Query::getTable($this->_table)->insert($dados);
            if ($result != false) {
                $this->_data['id'] = $result;
                $result = true;
            }
        } else {
            $result = Query::getTable($this->_table)->update($this->_paramsSet, $this->_data['id']);
        }

        // Refresh data
        $find = Query::getTable($this->_table)->find($this->_data['id']);
        $this->_data = $find->toArray();

        return $result;
    }

    public function delete ()
    {
        if (is_null($this->_data['id'])) {
            return false;
        }
        return Query::getTable($this->_table)->findDelete($this->_data['id']);
    }

    public function toArray ()
    {
        return $this->_data;
    }

    public function toArrayNum ()
    {
        return array_values($this->_data);
    }

    public function toJSON ($typeJSON = 0)
    {
        return json_encode($this->_data, $typeJSON);
    }
}

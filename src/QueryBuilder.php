<?php namespace Bmodel;

class QueryBuilder {
    private $data = [];
    private $querySql;

    public function __construct () {
        $this->data = [
            'table' => null,
            'select' => null,
            'join' => null,
            'where' => null,
            'params' => null,
            'order' => null,
            'start' => null,
            'limit' => null,
            'connectionId' => null
        ];
    }
    public function __set ($name, $value = null)
    {
        $this->data[$name] = $value;
    }
    public function __get ($name)
    {
        if (!array_key_exists($name, $this->data)) {
            throw new \Exception("Parametro '{$name}' não existe ou não configurado", 1);
        }
        return $this->data[$name];
    }

    public function getQuerySql ()
    {
        return $this->querySql;
    }

    public function getWhere ()
    {
        return isset($this->data['where']) && !is_null($this->data['where']) ? $this->data['where'] : '1';
    }
    public function setTableName ($table)
    {
        $this->data['table'] = Commons::snakeCase($table);
    }

    public function setConnectionId ($connectionId = null) {
        $this->data['connectionId'] = $connectionId;
    }

    public function select ($fields = null)
    {
        $this->data['select'] = $fields;
    }

    private function addJoin ($table, $on, $name = null, $type = 'INNER') {
        if (!isset($this->data['join'])) {
            $this->data['join'] = array();
        }

        if (!is_null($name)) {
            $name = Commons::snakeCase($name);
        }

        $this->data['join'][] = (object)[
            'type' => $type,
            'table' => Commons::snakeCase($table),
            'on' => $on,
            'name' => $name
        ];
    }

    public function innerJoin ($table, $on, $name = null)
    {
        $this->addJoin($table, $on, $name, 'INNER');
    }

    public function leftJoin ($table, $on, $name = null)
    {
        $this->addJoin($table, $on, $name, 'left');
    }

    public function rightJoin ($table, $on, $name = null)
    {
        $this->addJoin($table, $on, $name, 'right');
    }

    public function where ($where, $params = [])
    {
        $this->data['where'] = $where;
        $this->data['params'] = $params;
    }

    public function andWhere ($where, $params = [])
    {
        if (!isset($this->data['where'])) {
            $this->data['where'] = $where;
        } else {
            $this->data['where'] .= ' AND '.$where;
        }
        if (!isset($this->data['params'])) {
            $this->data['params'] = [];
        }
        $this->data['params'] = array_merge($this->data['params'], $params);
    }

    public function orderBy ($order) {
        $this->data['order'] = $order;
    }

    public function start ($start) {
        $this->data['start'] = $start;
    }

    public function limit ($limit)
    {
        $this->data['limit'] = $limit;
    }

    private function valuesToParams ($values) {
        $params = [];
        $i = 0;
        foreach ($values as $name => $value) {
            $params[':p_'.$i] = $value;
            $i++;
        }

        return $params;
    }

    /**
     * Inserir registro
     *
     * @param Array $values Valores dos campos [nome => valor]
     *
     * @return Boolean|Int False se erro  ou Retorna o id do registro
     * @author Jonas Ribeiro <jonasribeiro19@gmail.com>
     * @version 1.0
     */
    public function insert ($values)
    {
        $keys = [];
        foreach ($values as $key=>$value) {
            $keys[] = "`{$key}`";
        }
        $values = $this->valuesToParams($values);
        $keysParams = [];
        foreach ($values as $key=>$value) {
            $keysParams[] = "{$key}";
        }

        $this->data['params'] = $values;

        $this->querySql = "INSERT INTO `{$this->table}` ({$keys}) VALUES ({$keysParams})";
        $this->exec();
        $pdo = Connection::connect($this->data['connectionId']);
        return ((!$pdo)? false : $pdo->lastInsertId($name));
    }

    /**
     *
     *
     * @param Array $values Valores dos campos [nome => valor]
     * @param Int $id Id que será alterado ou se null pegar do where
     *
     * @return void
     * @author Jonas Ribeiro <jonasribeiro19@gmail.com>
     * @version 1.0
     */
    public function update ($values, $id = null)
    {
        $params = [];
        $i = 0;
        $valores = [];
        foreach ($values as $name => $value) {
            $params[':p_'.$i] = $value;
            $valores[] = "`$name` = :p_".$i;
            $i++;
        }

        if (!isset($this->data['params']) || is_null($this->data['params'])) {
            $this->data['params'] = [];
        }
        $this->data['params'][] = $params;

        $this->querySql = "UPDATE `{$this->table}` SET ".implode(", ", $valores). " WHERE ".$this->getWhere();
        return (!$this->exec())? false : true;
    }

    /**
     * Deletar usando o Where
     *
     * @return boolean true de deletado com successo
     * @author Jonas Ribeiro <jonasribeiro19@gmail.com>
     * @version 1.0
     */
    public function delete ()
    {
        $this->querySql = "DELETE FORM `{$this->table}` WHERE ".$this->getWhere();
        return (!$this->exec())? false : true;
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
    public function find ($id = null)
    {
        if (is_null($id)) {
            return $this->findBy();
        } else {
            return $this->findBy(['id' => $id]);
        }
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
    public function findBy ($params = [])
    {
        $bindData = [];
        if (is_string($params)) {
            if (!empty($params)) {
                $where = $params;
                $this->andWhere($where, []);
            }
        } else {
            $i = 0;
            $where = [];
            foreach ($params as $name => $value) {
                $bindData[':p_'.$i] = $value;
                $where[] = "`$name` = :p_".$i;
                $i++;
            }
            if (empty($where)) {
                $where = [1];
            } else {
                $where = implode(' AND ', $where);
                $this->andWhere($where, $bindData);
            }
        }

        $this->limit(1);

        $res = $this->get();

        if (!$res || $res->rowCount() == 0) return false;

        $model = Connection::getRequireModel($this->table);
        if (!$model) {
            $model = "\\Bmodel\\Record";
        }
        $res->setFetchMode(\PDO::FETCH_CLASS|\PDO::FETCH_PROPS_LATE, $model);
        return $res->fetch();
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
    public function findDelete ($id)
    {
        $obj = $this->find($id);
        if (!$obj) return false;

        return $obj->delete();
    }

    public function get ()
    {
        if (is_null($this->data['select']) || empty($this->data['select'])) {
            $fields = "*";
        } elseif (is_string($this->data['select'])) {
            $fields = $this->data['select'];
        } else {
            $fields = "";
            $sep = '';
            foreach ($this->data['select'] as $campo) {
                $fields .= $sep . $campo;
                $sep = ',';
            }
        }
        $this->querySql = "SELECT {$fields} FROM `{$this->table}` WHERE ".$this->getWhere();

        if (!is_null($this->order)) {
            $this->querySql .= " ORDER BY ".$this->order;
        }
        if (!is_null($this->limit)) {
            if (!is_null($this->start)) {
                $this->querySql .= " LIMIT ".$this->start.",".$this->limit;
            } else {
                $this->querySql .= " LIMIT ".$this->limit;
            }
        }
        return $this->exec();
    }

    public function getAll ($type = 0)
    {
        $res = $this->get();
        if (!$res || $res->rowCount() == 0) return false;

        $model = Connection::getRequireModel($this->table);
        if (!$model) {
            $model = "\\Bmodel\\Record";
        }

        $res->setFetchMode(\PDO::FETCH_CLASS|\PDO::FETCH_PROPS_LATE, $model);
        return new ResultsQuery($res->fetchAll(), $this->querySql, $this->data['params']);// return $res->fetchAll(\PDO::FETCH_CLASS, $model);
    }

    public function exec ()
    {
        return Query::query($this->querySql, $this->data['params'], $this->data['connectionId']);
    }
}

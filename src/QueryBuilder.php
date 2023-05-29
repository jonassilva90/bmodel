<?php

namespace Bmodel;

#[\AllowDynamicProperties]
class QueryBuilder extends Table
{
    private $data = [];
    private $bufferData = [];
    private $querySql;
    public function __construct()
    {
        $this->data['clearOnExec'] = true;
        $this->clearData();
    }
    public function __set($name, $value = null)
    {
        $this->data[$name] = $value;
    }

    public function __get($name)
    {
        if (!array_key_exists($name, $this->data)) {
            throw new \Exception("Parametro '{$name}' não existe ou não configurado", 1);
        }
        return $this->data[$name];
    }

    private function pushBufferData()
    {
        $this->bufferData[] = $this->data;
        return $this;
    }

    private function backBufferData()
    {
        $data = array_pop($this->bufferData);
        if (is_null($data)) {
            $this->data = [];
            $this->clearData();
        }
        $this->data = $data;
        return $this;
    }

    public function setClearOnExec($clearOnExec = true)
    {
        $this->data['clearOnExec'] = $clearOnExec;
        return $this;
    }

    public function getQuerySql(): string
    {
        return $this->querySql;
    }

    public function getWhere(): string
    {
        if (!isset($this->data['where']) || empty($this->data['where'])) {
            return "1=1";
        }

        $where = '';
        foreach ($this->data['where'] as $i => $item) {
            if ($i > 0) {
                $where .= " {$item->separator} " . $item->value;
            } else {
                $where .= $item->value;
            }
        }
        return $where;
    }

    public function getBindParams(): array
    {
        return $this->data['params'] ?? [];
    }

    public function select($fields = null)
    {
        if (is_null($fields)) {
            $this->data['select'] = '*';
        } elseif (is_array($fields)) {
            $this->data['select'] = implode(',', $fields);
        } else {
            $this->data['select'] = $fields;
        }
        return $this;
    }

    public function join($table, $on, $type = 'INNER', $name = null)
    {
        if (!isset($this->data['join'])) {
            $this->data['join'] = array();
        }

        if (!is_null($name)) {
            $name = Commons::snakeCase($name);
        }

        $this->data['join'][] = (object)[
            'type' => strtoupper($type),
            'table' => Commons::snakeCase($table),
            'on' => $on,
            'name' => $name
        ];
        return $this;
    }


    public function innerJoin($table, $on, $name = null)
    {
        return $this->join($table, $on, 'INNER', $name);
    }

    public function leftJoin($table, $on, $name = null)
    {
        return $this->join($table, $on, 'LEFT', $name);
    }

    public function rightJoin($table, $on, $name = null)
    {
        return $this->join($table, $on, 'RIGHT', $name);
    }

    public function where($where, $params = [], $separator = 'AND')
    {
        if (!isset($this->data['where'])) {
            $this->data['where'] = [];
        }

        $this->data['where'][] = (object)[
            'value' => $where,
            'separator' => $separator
        ];

        if (!isset($this->data['params'])) {
            $this->data['params'] = [];
        }

        if (!empty($params)) {
            $this->data['params'] = array_merge($this->data['params'], $params);
        }
        return $this;
    }
    public function andWhere($where, $params = [])
    {
        return $this->where($where, $params, 'AND');
    }

    public function orWhere($where, $params = [])
    {
        return $this->where($where, $params, 'OR');
    }

    public function beginGroupWhere($separator = 'AND')
    {
        return $this->where('(', [], $separator);
    }

    public function endGroupWhere()
    {
        return $this->where(')', [], '');
    }

    public function orderBy($order)
    {
        if (isset($this->data['order']) && !empty($this->data['order'])) {
            $this->data['order'] .= ',' . $order;
            return $this;
        }
        $this->data['order'] = $order;
        return $this;
    }

    public function start($start)
    {
        $this->data['start'] = $start;
        return $this;
    }

    public function limit($limit)
    {
        $this->data['limit'] = $limit;
        return $this;
    }

    private function clearData()
    {
        $this->data['where']  = [];
        $this->data['params'] = [];
        $this->data['select'] = '*';
        $this->data['join']   = [];
        $this->data['order']  = null;
        $this->data['start']  = null;
        $this->data['limit']  = null;
        return $this;
    }

    public function count(): int
    {
        $joins = '';

        foreach ($this->data['join'] as $join) {
            $tableJoin = "`{$join->table}`";
            $tableAlias = $join->name;
            if (!is_null($tableAlias) && !empty($tableAlias)) {
                $tableJoin .= " `{$tableAlias}`";
            }
            $joins .= "{$join->type} JOIN {$tableJoin} ON {$join->on} ";
        }
        $table = "`{$this->getTableName()}`";
        $tableAlias = $this->getTableAlias();
        if (!is_null($tableAlias) && !empty($tableAlias)) {
            $table .= " `{$tableAlias}`";
        }
        $this->querySql = "SELECT count(" . $this->getPrimaryKey() .
            ") FROM {$table} {$joins}WHERE " . $this->getWhere();

        $result = $this->exec();

        if (!$result) {
            return 0;
        }

        list($count) = $result->fetch(\PDO::FETCH_NUM);
        return $count;
    }

    public function insert($values, $returnInsertId = true)
    {
        if (empty($values)) {
            return false;
        }
        $this->pushBufferData();
        $fields = [];
        $keysParams = [];
        $params = [];
        $i = 0;
        foreach ($values as $field => $value) {
            $fields[] = $field;
            $keysParams[] = ':p_' . $i;
            $params[':p_' . $i] = $value;
            $i++;
        }

        $this->data['params'] = $params;

        $this->querySql = "INSERT INTO `{$this->getTableName()}` (" .
            implode(",", $fields) .
            ") VALUES (" .
            implode(",", $keysParams) . ")";

        if ($this->exec() === false) {
            $this->backBufferData();
            return false;
        }
        if (!$returnInsertId) {
            $this->backBufferData();
            return true;
        }
        $pdo = $this->getConn();
        $result = ((!$pdo) ? false : $pdo->lastInsertId());
        $this->backBufferData();
        return $result;
    }

    public function update($values, $id = null)
    {
        if (empty($values)) {
            return false;
        }
        $this->pushBufferData();
        $valuesSet = '';
        $separator = '';
        $i = 0;
        $params = [];
        foreach ($values as $field => $value) {
            $valuesSet .= "{$separator}{$field} = :p_{$i}";
            $params[':p_' . $i] = $value;
            $separator = ', ';
            $i++;
        }
        $this->data['params'] = array_merge($this->data['params'], $params);
        if (!is_null($id)) {
            $this->where($this->getPrimaryKey() . ' = :id', [':id' => $id]);
        }
        $this->querySql = "UPDATE `{$this->getTableName()}` SET {$valuesSet} WHERE " . $this->getWhere();

        $this->addQueryOrder();
        $this->addQueryLimit();
        $result = (!$this->exec()) ? false : true;
        $this->backBufferData();
        return $result;
    }

    public function delete($id = null): bool
    {
        if (!is_null($id)) {
            $this->where($this->getPrimaryKey() . ' = :id', [':id' => $id]);
        }
        $this->querySql = "DELETE FROM `{$this->getTableName()}` WHERE " . $this->getWhere();
        $this->addQueryOrder();
        $this->addQueryLimit();
        return (!$this->exec()) ? false : true;
    }


    /**
     * Traz um registro por id
     *
     * @param string|int $id Id dao registro
     *
     * @return Record|boolean False de nao encontrar
     * @author Jonas Ribeiro <jonasribeiro19@gmail.com>
     * @version 1.0
     */
    public function find($id = null)
    {
        if (is_null($id)) {
            return $this->findBy();
        } else {
            return $this->findBy([$this->getPrimaryKey() => $id]);
        }
    }

    /**
     * Traz primeiro registro por parametros
     *
     * @param array|string $params params para where
     *
     * @return Record|boolean False de nao encontrar
     * @author Jonas Ribeiro <jonasribeiro19@gmail.com>
     * @version 1.0
     */
    public function findBy($params = [])
    {
        $this->pushBufferData();
        if (is_string($params)) {
            if (!empty($params)) {
                $this->andWhere($params, []);
            }
        } else {
            $i = 0;
            $where = [];
            $bindData = [];
            foreach ($params as $name => $value) {
                $bindData[':p_' . $i] = $value;
                $where[] = "`$name` = :p_" . $i;
                $i++;
            }
            if (empty($where)) {
                $where = ['1=1'];
            }
            $where = implode(' AND ', $where);
            $this->andWhere($where, $bindData);
        }

        $this->limit(1);

        $res = $this->getAll();
        $this->backBufferData();

        if (!$res) {
            return false;
        }

        return $res->fetch();
    }

    public function get()
    {
        if (is_null($this->data['select']) || empty($this->data['select'])) {
            $fields = "*";
        } else {
            $fields = $this->data['select'];
        }

        $joins = '';

        foreach ($this->data['join'] as $join) {
            $tableJoin = "`{$join->table}`";
            $tableAlias = $join->name;
            if (!is_null($tableAlias) && !empty($tableAlias)) {
                $tableJoin .= " `{$tableAlias}`";
            }
            $joins .= "{$join->type} JOIN {$tableJoin} ON {$join->on} ";
        }
        $table = "`{$this->getTableName()}`";
        $tableAlias = $this->getTableAlias();
        if (!is_null($tableAlias) && !empty($tableAlias)) {
            $table .= " `{$tableAlias}`";
        }
        $this->querySql = "SELECT {$fields} FROM {$table} "
            . $joins
            . "WHERE " . $this->getWhere();

        $this->addQueryOrder();
        $this->addQueryLimit();
        return $this->exec();
    }

    /**
     * Execulta select e traz todos resultados
     *
     * @return ResultsQuery|boolean False se erro
     */
    public function getAll()
    {
        $connId = $this->getConnectionId();
        $res = $this->get();
        if (!$res || $res->rowCount() == 0) {
            return false;
        }

        return new ResultsQuery(
            $res,
            $this->getTableName(),
            $this->getTableAlias(),
            $this->getPrimaryKey(),
            $connId,
            $this->querySql
        );
    }

    private function addQueryOrder()
    {
        $order = $this->data['order'] ?? null;
        if (!is_null($order)) {
            $this->querySql .= " ORDER BY {$order}";
        }
    }
    private function addQueryLimit()
    {
        $start = $this->data['start'] ?? null;
        $limit = $this->data['limit'] ?? null;
        if (!is_null($start)) {
            $this->querySql .= " LIMIT {$start}";
            if (!is_null($limit)) {
                $this->querySql .= ", {$limit}";
            }
        } elseif (!is_null($limit)) {
            $this->querySql .= " LIMIT {$limit}";
        }
    }

    private function exec()
    {
        $result = Query::query($this->querySql, $this->getBindParams(), $this->getConnectionId());
        if ($this->data['clearOnExec']) {
            $this->clearData();
        }
        return $result;
    }

    public function beginTransaction()
    {
        $this->getConn()->beginTransaction();
        return $this;
    }

    public function commit()
    {
        return $this->getConn()->commit();
    }

    public function rollBack()
    {
        return $this->getConn()->rollBack();
    }
}

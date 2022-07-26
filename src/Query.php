<?php

namespace Bmodel;

use Bmodel\Connection;

class Query
{
    public static $printQuery = false;
    public static $queryString = '';
    public static $tables = [];
    /**
     * Begin transaction
     *
     * @param String|Int $connId Id da conexao
     *
     * @return Boolean
     * @author Jonas Ribeiro <jonasribeiro19@gmail.com>
     * @version 1.0
     */
    public static function beginTransaction($connId = null)
    {
        $pdo = Connection::connect($connId);
        return $pdo->beginTransaction();
    }
    /**
     * Commit transaction
     *
     * @param String|Tnt $connId Id da conexao
     *
     * @return Boolean
     * @author Jonas Ribeiro <jonasribeiro19@gmail.com>
     * @version 1.0
     */
    public static function commit($connId = null)
    {
        $pdo = Connection::connect($connId);
        return $pdo->commit();
    }
    /**
     * Rollback transaction
     *
     * @param String|Int $connId Id da conexao
     *
     * @return Boolean
     * @author Jonas Ribeiro <jonasribeiro19@gmail.com>
     * @version 1.0
     */
    public static function rollBack($connId = null)
    {
        $pdo = Connection::connect($connId);
        return $pdo->rollBack();
    }
    /** Execulta query sql
     *
     * @param string $sql
     * @param array $bindData Array com os valores para o bind.
     * @param int $connId Id da connection DEFAULT=NULL
     * @param boolean $reconnect Reconecta quando falha DEFAULT=true
     * @throws \Exception
     * @return boolean|\PDOStatement
     */
    public static function query($sql, $bindData = null, $connId = null, $reconnect = true)
    {
        if (Query::$printQuery) {
            echo "SQL: " . $sql . "<br />\r\n";
            echo "<pre>" . json_encode($bindData, JSON_PRETTY_PRINT) . "</pre>";
        }

        $pdo = Connection::connect($connId);
        if (is_null($bindData) || !is_array($bindData) || empty($bindData)) {
            self::$queryString = $sql;
            if (!$query = $pdo->query($sql)) {
                list($handle, $codError, $StrError) = $pdo->errorInfo();

                $codError = intval($codError);

                if ($reconnect && $codError == 2006) {
                    Connection::connect($connId, true);
                    return self::query($sql, $bindData, $connId, false);
                }

                throw new \Exception("Error: #{$codError}: {$StrError}<br />\r\n" . $sql, $codError);
                return false;
            }
        } else {
            $query = $pdo->prepare($sql);
            if (!$query->execute($bindData)) {
                list($handle, $codError, $StrError) = $query->errorInfo();

                $codError = intval($codError);
                self::$queryString = $query->queryString;

                if ($reconnect && $codError == 2006) {
                    Connection::connect($connId, true);
                    return self::query($sql, $bindData, $connId, false);
                }

                throw new \Exception("Error: #{$codError}: {$StrError}<br />\r\n", $codError);
                return false;
            }
            self::$queryString = $query->queryString;
        }

        return $query;
    }

    public static function isTable($table, $connId = null)
    {
        $connId = is_null($connId) ? 0 : $connId;
        if (isset(static::$tables[$connId]) && isset(static::$tables[$connId][$table])) {
            return true;
        }

        $pdo = Connection::connect($connId);
        $result = $pdo->query("SHOW TABLES");
        if (!isset(static::$tables[$connId])) {
            static::$tables[$connId] = [];
        }
        while ($row = $result->fetch()) {
            $tableCurrent = $row[0];
            if (!isset(static::$tables[$connId][$tableCurrent])) {
                static::$tables[$connId][$tableCurrent] = [];
            }
        }

        return (isset(static::$tables[$connId]) && isset(static::$tables[$connId][$table]));
    }

    /**
     * Pega lista de campos no banco de dados
     *
     * @param String $tableName
     * @param integer $type Tipo de retorno, 0 para array de Bmodel\Field / 1 para array de string
     *
     * @throws \Exception
     * @return Array
     * @author Jonas Ribeiro <jonasribeiro19@gmail.com>
     * @version 1.0
     */
    public static function getFieldsFromDB($tableName, $type = 0, $connId = null)
    {
        $connId = is_null($connId) ? 0 : $connId;

        if (isset(static::$tables[$connId][$tableName]) && count(static::$tables[$connId][$tableName]) > 0) {
            if ($type == 1) {
                return array_map(function ($field) {
                    return $field->getName();
                }, static::$tables[$connId][$tableName]);
            }
            return static::$tables[$connId][$tableName];
        }

        if (is_null($tableName) || !static::isTable($tableName, $connId)) {
            throw new \Exception("Table '{$tableName}' not exists");
        }
        $result = Query::query("SELECT * FROM `{$tableName}` WHERE 0 LIMIT 1", null, $connId);
        $c = $result->columnCount();
        $fields = [];
        for ($i = 0; $i < $c; $i++) {
            $f = $result->getColumnMeta($i);
            $fields[$f['name']] = new Field($f);
        }
        static::$tables[$connId][$tableName] = $fields;

        if ($type == 1) {
            return array_map(function ($field) {
                return $field->getName();
            }, static::$tables[$connId][$tableName]);
        }

        return static::$tables[$connId][$tableName];
    }

    /**
     * Pegar obj Table
     *
     * @param string $table Nome da tabela no formato PascalCase ou snake_case
     * @param string $alias Alias da tabela
     * @param string $primaryKey Campo primary key da tabela
     * @param int $connId ConnectionId
     *
     * @return Model
     * @author Jonas Ribeiro <jonasribeiro19@gmail.com>
     * @version 1.0
     */
    public static function getTable($table, $alias = null, $primaryKey = null, $connId = null): Model
    {
        if ($model = Connection::searchModel($table)) {
            $connId = is_null($connId) ? $model->connId : $connId;
            $classModel = $model->classModel;
            require_once $model->file;
            $t = new $classModel();
            if (!is_null($table) && !is_null($alias)) {
                $t->setTableName($table, $alias);
            } elseif (!is_null($alias)) {
                $table = $t->getTableName();
                $t->setTableName($table, $alias);
            }
            if (!is_null($primaryKey)) {
                $t->setPrimaryKey($primaryKey);
            }
            if (!is_null($connId)) {
                $t->setConnectionId($connId);
            }
            $t->defineFields();
            $t->defineRelations();
            return $t;
        }

        if (is_null($connId)) {
            $connId = 0;
        }
        $table = Commons::snakeCase($table);

        if (!self::isTable($table, $connId)) {
            throw new \Exception("Table '{$table}' not exists");
        }

        if (is_null($primaryKey)) {
            $primaryKey = 'id';
        }

        $t = new Model();
        $t->setConf($table, $alias, $primaryKey, $connId);
        return $t;
    }
}

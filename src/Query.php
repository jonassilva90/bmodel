<?php

namespace Bmodel;

use Bmodel\Connection;

class Query
{
    public static $printQuery = false;
    public static $queryString = '';
    /**
     * Begin transaction
     *
     * @param String|Int $connectionsId Id da conexao
     *
     * @return Boolean
     * @author Jonas Ribeiro <jonasribeiro19@gmail.com>
     * @version 1.0
     */
    public static function beginTransaction($connectionsId = null)
    {
        $pdo = Connection::connect($connectionsId);
        return $pdo->beginTransaction();
    }
    /**
     * Commit transaction
     *
     * @param String|Tnt $connectionsId Id da conexao
     *
     * @return Boolean
     * @author Jonas Ribeiro <jonasribeiro19@gmail.com>
     * @version 1.0
     */
    public static function commit($connectionsId = null)
    {
        $pdo = Connection::connect($connectionsId);
        return $pdo->commit();
    }
    /**
     * Rollback transaction
     *
     * @param String|Int $connectionsId Id da conexao
     *
     * @return Boolean
     * @author Jonas Ribeiro <jonasribeiro19@gmail.com>
     * @version 1.0
     */
    public static function rollBack($connectionsId = null)
    {
        $pdo = Connection::connect($connectionsId);
        return $pdo->rollBack();
    }
    /** Execulta query sql
     *
     * @param string $sql
     * @param array $bindData Array com os valores para o bind.
     * @param int $connectionsId Id da connection DEFAULT=NULL
     * @throws \Exception
     * @return boolean|\PDOStatement
     */
    public static function query($sql, $bindData = null, $connectionsId = null)
    {
        if (Query::$printQuery) {
            echo "SQL: " . $sql . "<br />\r\n";
        }

        $pdo = Connection::connect($connectionsId);
        if (is_null($bindData) || empty($bindData)) {
            self::$queryString = $sql;
            if (!$query = $pdo->query($sql)) {
                list($handle, $codError, $StrError) = $pdo->errorInfo();

                throw new \Exception("Error: #{$codError}: {$StrError}<br />\r\n" . $sql, $codError);
                return false;
            }
        } else {
            $query = $pdo->prepare($sql);
            if (!$query->execute($bindData)) {
                list($handle, $codError, $StrError) = $query->errorInfo();
                self::$queryString = $query->queryString;

                throw new \Exception("Error: #{$codError}: {$StrError}<br />\r\n", $codError);
                return false;
            }
            self::$queryString = $query->queryString;
        }

        return $query;
    }

    /**
     * Pegar obj Table
     *
     * @param String $table Nome da tabela no formato PascalCase ou snake_case
     * @param String $alias Alias da tabela
     * @param String $primaryKey Campo primary key da tabela
     *
     * @return Table
     * @author Jonas Ribeiro <jonasribeiro19@gmail.com>
     * @version 1.0
     */
    public static function getTable($table, $alias = null, $primaryKey = 'id')
    {
        // if (is_null($alias)) $alias = Commons::snake_case($table);
        $objTable = Connection::getTable($table);
        $objTable->setPrimaryKey($primaryKey);
        if (!$objTable) {
            return Table::createPseudo($table);
        }

        return $objTable;
    }
}

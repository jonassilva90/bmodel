<?php
namespace Bmodel;

use Bmodel\Connection;

class Query {
    static $printQuery = false;
    static $queryString = '';
    /**
     * Begin transaction
     *
     * @param String|Int $connectionsId Id da conexao
     *
     * @return Boolean
     * @author Jonas Ribeiro <jonasribeiro19@gmail.com>
     * @version 1.0
     */
    static public function beginTransaction($connectionsId = null)
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
    static public function commit($connectionsId = null)
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
    static public function rollBack($connectionsId = null)
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
    static public function query($sql,$bindData = null,$connectionsId = null)
    {
        if (Query::$printQuery) {
            echo "SQL: ".$sql."<br />\r\n";
        }
        $pdo = Connection::connect($connectionsId);
        if(is_null($bindData) || empty($bindData)){
            self::$queryString = $sql;
            if(!$query = $pdo->query($sql)){
                list($handle, $codError, $StrError) = $pdo->errorInfo();

                throw new \Exception("Error: #{$codError}: {$StrError}<br />\r\n".$sql,$codError);
                return false;
            }
        } else {
            $query = $pdo->prepare($sql);
            if(!$query->execute( $bindData )){
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
     *
     * @return void
     * @author Jonas Ribeiro <jonasribeiro19@gmail.com>
     * @version 1.0
     */
    static function getTable ($table, $alias = null)
    {
        // if (is_null($alias)) $alias = Commons::snake_case($table);
        $objTable = Connection::getTable($table);
        if (!$objTable) {
            return Table::createPseudo($table);
        }

        return $objTable;

    }

}

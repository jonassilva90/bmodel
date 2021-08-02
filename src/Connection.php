<?php

namespace Bmodel;

class Connection
{
    public static $connections = [];
    public static $cfgConnections = [];
    public static $tables = [];
    public static $timeZone = 'America/Sao_Paulo';
    public static $charset = 'UTF8';
    public static $modelPath;
    public static $namespaceModel = 'Model';
    /**
     *
     * @param string $dbname Nome do banco de dados
     * @param int $connectionsId Id da connection DEFAULT=NULL
     * @param string $host Hostname do banco de dados
     * @param string $port Porta do banco DEFAULT=NULL (3306)
     * @param string $username Usuario do Banco
     * @param string $password Senha do usuario
     * @param string $driver Driver de accesso ao banco DEFAULT NULL (Somente: mysql)
     * @throws \Exception
     * @return boolean
     */
    public static function setConnection(
        $dbname,
        $connectionsId = null,
        $host = null,
        $port = null,
        $username = null,
        $password = null,
        $driver = null
    ) {
        $connectionsId = $connectionsId ?? 0;
        $driver = $driver ?? 'mysql';
        $host = $host ?? 'localhost';
        $port = $port ?? '3306';
        $username = $username ?? 'root';
        $password = $password ?? '';

        $driversAceitos = array('mysql');
        if (!in_array($driver, $driversAceitos)) {
            throw new \Exception("Driver Driver '{$driver}' not accepted.");
            return false;
        }

        self::$cfgConnections[$connectionsId] = array(
            'driver' => $driver,
            'host' => $host,
            'port' => $port,
            'dbname' => $dbname,
            'username' => $username,
            'password' => $password
        );
        return true;
    }
    /**
     *
     * @param int $connectionsId Id da connection DEFAULT=NULL
     * @param boolean $autoConnect Connect if not conected
     * @throws \Exception
     * @return \PDO|boolean
     */
    public static function connect($connectionsId = null, $autoConnect = true)
    {
        $connectionsId = $connectionsId ?? 0;

        if (isset(self::$connections[$connectionsId])) {
            return self::$connections[$connectionsId];
        }

        if (!$autoConnect) {
            return false;
        }

        if (!isset(self::$cfgConnections[$connectionsId])) {
            throw new \Exception("Banco de dados não configurado");
            return false;
        }

        $driver = self::$cfgConnections[$connectionsId]['driver'];
        $host = self::$cfgConnections[$connectionsId]['host'];
        $port = self::$cfgConnections[$connectionsId]['port'];
        $dbname = self::$cfgConnections[$connectionsId]['dbname'];
        $username = self::$cfgConnections[$connectionsId]['username'];
        $password = self::$cfgConnections[$connectionsId]['password'];

        $options = array();
        $options[\PDO::ATTR_PERSISTENT] = false;
        if ($driver == "mysql") {
            //$options[\PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES 'UTF8'";
            $options[1002] = "SET NAMES '" . self::$charset . "'";
        }

        try {
            self::$connections[$connectionsId] = new \PDO(
                "{$driver}:host={$host};port={$port};dbname={$dbname};charset=" . self::$charset . "",
                $username,
                $password,
                $options
            );
            self::$connections[$connectionsId]->exec("SET time_zone = '" . self::$timeZone . "'");
        } catch (\PDOException $e) {
            self::$connections[$connectionsId] = null;
            throw new \Exception("Connection failed: " . utf8_encode($e->getMessage()) . ".");
            return false;
        }

        return self::$connections[$connectionsId];
    }

    public static function isTable($table, $connectionsId = null)
    {
        if (is_null($connectionsId)) {
            $con = 0;
        }
        if (isset(static::$tables[$con])) {
            if (in_array($table, static::$tables[$con])) {
                return true;
            }
        }

        $pdo = Connection::connect($connectionsId);
        $result = $pdo->query("SHOW TABLES");
        static::$tables[$con] = [];
        while ($row = $result->fetch()) {
            static::$tables[$con][] = $row[0];
        }
        return in_array($table, static::$tables[$con]);
    }

    /**
     * Define Caminho dos arquivos model
     */
    public static function setModelPath($path)
    {
        self::$modelPath = $path;
    }

    /**
     * Traz o caminho dos models
     *
     * @param String $table Nome da tabela no formato PascalCase ou snake_case
     * Se $table for null, retorna somente a pasta dos models
     * Se não for null, retorna o caminho do modelo da tabela
     * Se não encontrar o arquivo retorna FALSE
     *
     * @return String|Boolean False de não encontrar caminho
     * @author Jonas Ribeiro <jonasribeiro19@gmail.com>
     * @version 1.0
     */
    public static function getModelPath($table = null)
    {
        if (is_null(self::$modelPath)) {
            throw new \Exception("ModelPath undefined", 1);
        }

        if (is_null($table)) {
            return self::$modelPath;
        }
        $modelName = Commons::pascalCase($table);
        $modelPath = self::$modelPath . "/" . $modelName . ".php";
        $modelPath = str_replace('\\', "/", $modelPath);

        if (!is_file($modelPath)) {
            return false;
        }
        return $modelPath;
    }

    /**
     * Traz o caminho dos tables
     *
     * @param String $table Nome da tabela no formato PascalCase ou snake_case
     * Se $table for null, retorna somente a pasta dos models
     * Se não for null, retorna o caminho do modelo da tabela
     * Se não encontrar o arquivo retorna FALSE
     *
     * @return String|Boolean False de não encontrar caminho
     * @author Jonas Ribeiro <jonasribeiro19@gmail.com>
     * @version 1.0
     */
    public static function getTablePath($table = null)
    {
        if (is_null(self::$modelPath)) {
            throw new \Exception("ModelPath undefined", 1);
        }

        if (is_null($table)) {
            return self::$modelPath;
        }
        $modelName = Commons::pascalCase($table);
        $modelPath = self::$modelPath . "/" . $modelName . "Table.php";
        $modelPath = str_replace('\\', "/", $modelPath);
        /*
        if (!is_file($modelPath)) {
            return false;
        }*/
        return $modelPath;
    }

    /**
     * New Table object
     *
     * @param String $table nome da tabela
     *
     * @return Table|boolean
     * @author Jonas Ribeiro <jonasribeiro19@gmail.com>
     * @version 1.0
     */
    public static function getTable($table = null)
    {
        $tablePath = self::getTablePath($table);
        $modelName = Commons::pascalCase($table) . "Table";
        $classTable = "\\" . str_replace('/', "\\", self::$namespaceModel . "/" . $modelName);

        if (!is_file($tablePath)) {
            return false;
        }

        require_once($modelPath);

        return new $classTable();
    }

    /**
     * Traz model da tabela
     *
     * @param String $table
     *
     * @return void
     * @author Jonas Ribeiro <jonasribeiro19@gmail.com>
     * @version 1.0
     */
    public static function getModel($table)
    {
        $modelPath = self::getModelPath($table);
        $modelName = Commons::pascalCase($table);
        $classModel = "\\" . str_replace('/', "\\", self::$namespaceModel . "/" . $modelName);

        if (!is_file($modelPath)) {
            return false;
        }

        require_once($modelPath);
        $model =  new $classModel($table);
        $model->setTableName($table);
        $model->reviewFields();
        return $model;
    }

    /**
     * Traz model da tabela (retorna o namespace\model)
     *
     * @param String $table
     *
     * @return void
     * @author Jonas Ribeiro <jonasribeiro19@gmail.com>
     * @version 1.0
     */
    public static function getRequireModel($table)
    {
        $modelPath = self::getModelPath($table);
        $modelName = Commons::pascalCase($table);
        $classModel = "\\" . str_replace('/', "\\", self::$namespaceModel . "/" . $modelName);

        if (!is_file($modelPath)) {
            return false;
        }

        require_once($modelPath);
        return $classModel;
    }
}

<?php
namespace Bmodel;

class Connection {
    static $connections = [];
    static $cfgConnections = [];
    static $timeZone = 'America/Sao_Paulo';
    static $charset = 'UTF8';
    static $modelPath;
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
    static function setConnection($dbname,$connectionsId = null,$host = NULL,$port=NULL,$username = NULL,$password = NULL,$driver = NULL){
        $connectionsId = $connectionsId??0;
        $driver = $driver??'mysql';
        $host = $host??'localhost';
        $port = $port??'3306';
        $username = $username??'root';
        $password = $password??'';

        $driversAceitos = array('mysql');
        if(!in_array($driver,$driversAceitos)){
            throw new \Exception("Driver Driver '{$driver}' not accepted.");
            return false;
        }

        self::$cfgConnections[$connectionsId] = array(
            'driver'=>$driver,
            'host'=>$host,
            'port'=>$port,
            'dbname'=>$dbname,
            'username'=>$username,
            'password'=>$password
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
    static public function connect($connectionsId = null, $autoConnect = true)
    {
        $connectionsId = $connectionsId??0;

        if(isset(self::$connections[$connectionsId]))
            return self::$connections[$connectionsId];

        if(!$autoConnect)
            return false;

        if(!isset(self::$cfgConnections[$connectionsId])){
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
        if($driver=="mysql"){
            //$options[\PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES 'UTF8'";
            $options[1002] = "SET NAMES '".self::$charset."'";
        }

        try {
            self::$connections[$connectionsId] = new \PDO(
                "{$driver}:host={$host};port={$port};dbname={$dbname};charset=".self::$charset."",
                $username,
                $password,
                $options
            );
            self::$connections[$connectionsId]->exec("SET time_zone = '". self::$timeZone . "'");
        } catch (\PDOException $e) {
            self::$connections[$connectionsId] = null;
            throw new \Exception("Connection failed: " . utf8_encode( $e->getMessage() ).".");
            return false;
        }

        return self::$connections[$connectionsId];
    }
    /**
     * Define Caminho dos arquivos model
     */
    public static function setModelPath ($path)
    {
        self::$modelPath = $path;
    }
}

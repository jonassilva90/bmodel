<?php

namespace Bmodel;

class Connection
{
    public static $connections = [];
    public static $cfgConnections = [];
    public static $modelsPaths = [];
    /**
     *
     * @param ConfigConnection $config
     * @throws \Exception
     * @return boolean
     */
    public static function setConnection(ConfigConnection $config)
    {
        $driversAllow = array('mysql');
        if (!in_array($config->driver, $driversAllow)) {
            throw new \Exception("Driver Driver '{$config->driver}' not accepted.");
            return false;
        }

        self::$cfgConnections[$config->id] = $config;
        return true;
    }
    /**
     *
     * @param int $connId Id da connection DEFAULT=NULL
     * @param boolean $autoConnect Connect if not conected
     * @throws \Exception
     * @return \PDO|boolean
     */
    public static function connect($connId = null, $autoConnect = true)
    {
        $connId = $connId ?? 0;

        if (isset(self::$connections[$connId])) {
            return self::$connections[$connId];
        }

        if (!$autoConnect) {
            return false;
        }

        if (!isset(self::$cfgConnections[$connId])) {
            throw new \Exception("Banco de dados não configurado");
            return false;
        }

        $driver = self::$cfgConnections[$connId]->driver;
        $host = self::$cfgConnections[$connId]->host;
        $port = self::$cfgConnections[$connId]->port;
        $dbname = self::$cfgConnections[$connId]->dbname;
        $username = self::$cfgConnections[$connId]->username;
        $password = self::$cfgConnections[$connId]->password;
        $persistent = self::$cfgConnections[$connId]->persistent;
        $charset = self::$cfgConnections[$connId]->charset;
        $timeZone = self::$cfgConnections[$connId]->timeZone;

        $options = array();
        $options[\PDO::ATTR_PERSISTENT] = !!$persistent;
        if ($driver == "mysql") {
            //$options[\PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES 'UTF8'";
            $options[1002] = "SET NAMES '" . $charset . "'";
        }

        try {
            self::$connections[$connId] = new \PDO(
                "{$driver}:host={$host};port={$port};dbname={$dbname};charset=" . $charset . "",
                $username,
                $password,
                $options
            );
            self::$connections[$connId]->exec("SET time_zone = '" . $timeZone . "'");
        } catch (\PDOException $e) {
            self::$connections[$connId] = null;
            throw new \Exception("Connection failed: " . utf8_encode($e->getMessage()) . ".");
            return false;
        }

        return self::$connections[$connId];
    }

    public static function addModelPath($modelPath, $modelNamespace, $connId = null)
    {
        $modelPath = str_replace(['/','\\'], DIRECTORY_SEPARATOR, $modelPath);
        $modelNamespace = str_replace('/', '\\', $modelNamespace);
        static::$modelsPaths[] = (object)[
            'path' => $modelPath,
            'namespace' => $modelNamespace,
            'connId' => $connId
        ];
    }

    public static function searchModel($tableName)
    {
        $modelName = Commons::pascalCase($tableName);

        foreach (static::$modelsPaths as $obj) {
            $file = $obj->path . DIRECTORY_SEPARATOR . $modelName . ".php";
            $classPath = $obj->namespace . '\\' . $modelName;
            $classPath = str_replace('/', '\\', $classPath);
            if (is_file($file)) {
                return (object)[
                    'file' => $file,
                    'classModel' => $classPath,
                    'connId' => $obj->connId
                ];
            }
        }
        return false;
    }
}

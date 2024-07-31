<?php

namespace Bmodel;

class Connection
{
    public static $connections = [];
    public static $cfgConnections = [];
    public static $modelsPaths = [];
    public static $connIdDefault = 0;
    /**
     *
     * @param ConfigConnection $config
     * @throws \Exception
     * @return boolean
     */
    public static function setConnection(ConfigConnection $config)
    {
        $driversAllow = array('mysql', 'sqlite');
        if (!in_array($config->driver, $driversAllow)) {
            throw new \Exception("Driver Driver '{$config->driver}' not accepted.");
            return false;
        }

        self::$cfgConnections[$config->id] = $config;
        return true;
    }

    public static function addConnection(ConfigConnection $config, $setDefault = true)
    {
        $idConn = self::getConnIdByConfigConnection($config);
        if (is_null($idConn)) {
            if (!is_null($config->id)) {
                $idConn = $config->id;
                self::setConnection($config);
            } else {
                $ids = array_keys(self::$cfgConnections);
                if (!empty($ids)) {
                    $idConn = max($ids) + 1;
                } else {
                    $idConn = 0;
                }
                $config->id = $idConn;
                self::setConnection($config);
            }
        }

        if ($setDefault) {
            self::setConnIdDefault($idConn);
        }
    }

    public static function getConnIdByConfigConnection(ConfigConnection $config): ?int
    {
        $id  = $config->id;
        $dns = $config->getDNS();
        
        if (isset(self::$cfgConnections[$id])) {
            if (self::$cfgConnections[$id]->getDNS() == $dns) {
                return $id;
            }
        }
        foreach (self::$cfgConnections as $idCurrent=>$configCurrent) {
            if ($configCurrent->getDNS() == $dns) {
                return $idCurrent;
            }
        }

        return null;
    }

    public static function setConnIdDefault(int $connId)
    {
        self::$connIdDefault = $connId;
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
        $connId = $connId ?? self::$connIdDefault;

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

        $driver     = self::$cfgConnections[$connId]->driver;
        $host       = self::$cfgConnections[$connId]->host;
        $filename   = self::$cfgConnections[$connId]->filename;
        $port       = self::$cfgConnections[$connId]->port;
        $dbname     = self::$cfgConnections[$connId]->dbname;
        $username   = self::$cfgConnections[$connId]->username;
        $password   = self::$cfgConnections[$connId]->password;
        $persistent = self::$cfgConnections[$connId]->persistent;
        $charset    = self::$cfgConnections[$connId]->charset;
        $timeZone   = self::$cfgConnections[$connId]->timeZone;

        $options = array();
        $options[\PDO::ATTR_PERSISTENT] = !!$persistent;
        $dsn = "";
        if ($driver == "mysql") {
            //$options[\PDO::MYSQL_ATTR_INIT_COMMAND] = "SET NAMES 'UTF8'";
            $options[1002] = "SET NAMES '" . $charset . "'";
            $dsn = "{$driver}:host={$host};port={$port};dbname={$dbname};charset=" . $charset . "";
        } elseif ($driver == "sqlite") {
            $dsn = "{$driver}:{$filename}";
        }

        try {
            self::$connections[$connId] = new \PDO(
                $dsn,
                $username,
                $password,
                $options
            );

            if($driver == 'mysql') {
                self::$connections[$connId]->exec("SET time_zone = '" . $timeZone . "'");
            } elseif($driver == 'sqlite') {
                if($charset=='UTF8') {
                    $charset = 'UTF-8';
                }
                self::$connections[$connId]->exec("PRAGMA encoding = '".$charset."';");;
            }
        } catch (\PDOException $e) {
            $message = mb_convert_encoding($e->getMessage(), 'UTF-8');
            self::$connections[$connId] = null;
            throw new \Exception("Connection failed: " . $message . ".");
            return false;
        }

        return self::$connections[$connId];
    }

    public static function inTransaction($connId = null): bool
    {
        $connId = $connId ?? self::$connIdDefault;
        if (!isset(self::$connections[$connId])) {
            return false;
        }
        return self::$connections[$connId]->inTransaction();
    }

    public static function closeConnect($connId = null)
    {
        $connId = $connId ?? self::$connIdDefault;

        if (isset(self::$connections[$connId])) {
            $conn = self::$connections[$connId];
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            unset(self::$connections[$connId]);
        }
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
            $connId = $obj->connId ?? self::$connIdDefault;
            $file = $obj->path . DIRECTORY_SEPARATOR . $modelName . ".php";
            $classPath = $obj->namespace . '\\' . $modelName;
            $classPath = str_replace('/', '\\', $classPath);
            if (is_file($file)) {
                return (object)[
                    'file' => $file,
                    'classModel' => $classPath,
                    'connId' => $connId
                ];
            }
            $file = $obj->path . DIRECTORY_SEPARATOR . $modelName . "Table.php";
            $classPath = $obj->namespace . '\\' . $modelName . "Table";
            $classPath = str_replace('/', '\\', $classPath);
            if (is_file($file)) {
                return (object)[
                    'file' => $file,
                    'classModel' => $classPath,
                    'connId' => $connId
                ];
            }
        }
        return false;
    }
}

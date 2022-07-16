<?php

namespace Bmodel;

class ConfigConnection
{
    public $dbname;
    public $id = 0;
    public $host = 'localhost';
    public $port = '3306';
    public $username = 'root';
    public $password = '';
    public $driver = 'mysql';
    public $persistent = false;
    public $timeZone = 'America/Sao_Paulo';
    public $charset = 'UTF8';
    public function __construct($data = [])
    {
        if (isset($data['dbname'])) {
            $this->dbname = $data['dbname'];
        }

        if (isset($data['id'])) {
            $this->id = $data['id'];
        }

        if (isset($data['host'])) {
            $this->host = $data['host'];
        }

        if (isset($data['port'])) {
            $this->port = $data['port'];
        }

        if (isset($data['username'])) {
            $this->username = $data['username'];
        }

        if (isset($data['password'])) {
            $this->password = $data['password'];
        }

        if (isset($data['driver'])) {
            $this->driver = $data['driver'];
        }

        if (isset($data['persistent'])) {
            $this->persistent = $data['persistent'];
        }

        if (isset($data['timeZone'])) {
            $this->timeZone = $data['timeZone'];
        }

        if (isset($data['charset'])) {
            $this->charset = $data['charset'];
        }
    }

    public function setConnection()
    {
        Connection::setConnection($this);
    }
}

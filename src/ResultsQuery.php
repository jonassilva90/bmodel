<?php

namespace Bmodel;

class ResultsQuery implements \IteratorAggregate
{
    /**
     *
     * @var \PDOStatement
     */
    private $pdoStat;
    private $querySql;
    private $tableName;
    private $tableAlias;
    private $primaryKey = 'id';
    private $connId;
    public function __construct(
        \PDOStatement $pdoStat,
        string $tableName,
        $tableAlias = null,
        string $primaryKey = 'id',
        ?int $connId = null,
        string $querySql = ''
    ) {
        $this->querySql = $querySql;
        $this->pdoStat = $pdoStat;
        $this->tableName = $tableName;
        $this->tableAlias = $tableAlias;
        $this->primaryKey = $primaryKey;
        $this->connId = $connId;
    }

    public function getIterator(): \Iterator
    {
        return $this->pdoStat->getIterator();
    }

    public function getQuerySql()
    {
        return $this->querySql;
    }

    public function count()
    {
        return $this->pdoStat->rowCount();
    }

    public function fetch()
    {
        if (!$row = $this->pdoStat->fetch(\PDO::FETCH_ASSOC)) {
            return false;
        }

        $record = Query::getTable(
            $this->tableName,
            $this->tableAlias,
            $this->primaryKey,
            $this->connId
        );
        $record->setData($row);
        return $record;
    }

    public function fetchAll()
    {
        $itens = [];

        while ($row = $this->fetch()) {
            $itens[] = $row;
        }

        return $itens;
    }

    public function toArray()
    {
        return $this->pdoStat->fetchAll(\PDO::FETCH_ASSOC);
    }

    public function toArrayNum()
    {
        return $this->pdoStat->fetchAll(\PDO::FETCH_NUM);
    }

    public function toJSON($typeJSON = 0)
    {
        return json_encode($this->toArray(), $typeJSON);
    }
}

<?php namespace Bmodel;

class Table {
    static $connectionId;
    static $tableName;
    static $fields = [];
    static $relations =[];
    public function __construct ()
    {
        $this->defineConnection();
        $this->defineTable();
        $this->defineFields();
        $this->defineRelations();
    }
    public function defineConnection ()
    {
        self::$connectionId = null;
    }
    public function defineTable ()
    {
        self::$tableName = '';
    }
    public function defineFields ()
    {
        self::$fields = $this->getFieldsFromDB();
    }
    public function defineRelations ()
    {

    }

    public static function getConnection ()
    {
        return Connection::connect(self::$connectionId);
    }

    /**
     * Pega lista de campos no banco de dados
     *
     * @param integer $type Tipo de retorno, 0 para array de Bmodel\Field / 1 para array de string
     *
     * @throws \Exception
     * @return Array
     * @author Jonas Ribeiro <jonasribeiro19@gmail.com>
     * @version 1.0
     */
    public function getFieldsFromDB($type = 0){
        $table = self::$tableName;
        $connectionsId = self::$connectionId;
        if(is_null($table) || !static::isTable($table,$connectionsId)){
            throw new \Exception("Table '{$table}' not exists");
        }
        //-------------------------------------------------//
        $result = static::query("SELECT * FROM `{$table}` WHERE 0 LIMIT 1",null,$connectionsId);
        $c = $result->columnCount();
        $fields = [];

        for($i=0;$i<$c;$i++){
            $f = $result->getColumnMeta($i);
            if ($arrayNames) {
                $fields = $f['name'];
            } else {
                $fields[ $f['name'] ] = new Field( $f );
            }
        }

        return $fields;
    }
    public static function getTable ()
    {
        return new static();
    }
}

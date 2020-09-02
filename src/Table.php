<?php namespace Bmodel;

class Table {
    static $connectionId;
    static $tableName;
    static $fields = [];
    static $relations =[];
    private $queryBuild;
    public function __construct ()
    {
        $this->defineConnection();
        $this->defineTable();
        $this->defineFields();
        $this->defineRelations();
    }

    public static function createPseudo ($tableName)
    {
        // Criando um pseudo class para a table (quando nao existir o Table)
        $table = new class() extends Table {
            static $tableName;
            public function setTable  ($table) {
                $this->tableName = Commons::snake_case($table);
                $this->fields = $this->getFieldsFromDB(0);
            }
        };
        $table->setTable($tableName);

        return $table;
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

    /**
     * Pegar obj Table da tabela
     *
     * @param String $table Nome da tabela no formato PascalCase ou snake_case
     * @param String $alias Alias da tabela
     *
     * @return void
     * @author Jonas Ribeiro <jonasribeiro19@gmail.com>
     * @version 1.0
     */
    public static function getTable ($table, $alias = null)
    {
        if (is_null($alias)) $alias = Commons::snake_case($table);

        $table = Connection::getTable($table);

        if (!$table) {
            // Criando um pseudo class para a table (quando nao existir o Table)
            $table = self::createPseudo($table);
        }

        return $table;
    }

    public function getBuild ()
    {
        if(is_null($this->queryBuild)) {
            $this->queryBuild = new QueryBuilder();
        }
        return $this->queryBuild;
    }

    public function clearBuild ()
    {
        $this->queryBuild = null;
    }

    public function select ($fields = null)
    {

    }

    public function innerJoin ($table, $on, $name)
    {

    }

    public function leftJoin ($table, $on, $name)
    {

    }

    public function rightJoin ($table, $on, $name)
    {

    }

    public function where ($where, $params = [])
    {

    }

    public function andWhere ($where, $params = [])
    {

    }

    public function orderBy ($order) {

    }

    public function start ($start) {

    }

    public function limit ($limit)
    {

    }

    public function insert ($values)
    {

    }

    public function update ($values, $id = null)
    {

    }

    public function delete ()
    {
        return $this->getBuild()->delete();
    }


    /**
     * Traz um registro por id
     *
     * @param String|Int $id Id dao registro
     *
     * @return Record|Boolean False de nao encontrar
     * @author Jonas Ribeiro <jonasribeiro19@gmail.com>
     * @version 1.0
     */
    public function find ($id)
    {
        return $this->findBy(['id' => $id]);
    }

    /**
     * Traz um registro por parametro
     *
     * @param Array|String $params Array com os valores ou um Where(String)
     * Exemplo: ['active' => 1] ou 'active = 1'
     *
     * @return Record|Boolean False de nao encontrar
     * @author Jonas Ribeiro <jonasribeiro19@gmail.com>
     * @version 1.0
     */
    public function findBy ($params)
    {

    }

    /**
     * Traz um registro e Apaga
     *
     * @param String|Int $id Id dao registro
     *
     * @return Record|Boolean False de nao encontrar ou não apagar
     * @author Jonas Ribeiro <jonasribeiro19@gmail.com>
     * @version 1.0
     */
    public function findDelete ($id)
    {
        $obj = $this->find($id);
        if (!$obj) return false;

        return $obj->delete();
    }

    public function get ()
    {

    }

    public function getAll ($type = 0)
    {

    }
}

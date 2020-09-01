<?php namespace Bmodel;

class Field {

    private $table;
    private $name;
    private $label;
    private $defaultValue;
    private $nativeType;
    private $pdoType;
    private $len;
    private $precision;
    private $type;
    private $pattern;
    private $required = false;
    private $inRequest = true;

    const TYPE_VARCHAR  = 0;
    const TYPE_TEXT     = 1;
    const TYPE_INTEGER  = 2;
    const TYPE_FLOAT    = 3;
    const TYPE_DATE     = 4;
    const TYPE_DATETIME = 5;
    const TYPE_TIME     = 6;
    const TYPE_HIDDEN = 7;
    const TYPE_BOOLEAN = 8;
    const TYPE_COLORRGB = 9;


    public function __construct($columnMeta){
        $this->table = $columnMeta['table']??null;
        $this->name = $columnMeta['name']??null;
        $this->label = $this->name;
        $this->nativeType = $columnMeta['native_type']??null;
        $this->pdoType = $columnMeta['pdo_type']??null;
        $this->len = $columnMeta['len']??null;
        $this->precision = $columnMeta['precision']??null;
        $this->pattern = '/.+/';
        switch (strtolower($this->nativeType)){
            case 'integer':// -9999
                $this->type = self::TYPE_INTEGER;
                $this->pattern = '/^([-])?[0-9]+$/';
                break;
            case 'number':// -9999.99
            case 'float':
            case 'double':
                $this->type = self::TYPE_FLOAT;
                $this->pattern = '/^([-])?[0-9]+([.][0-9]+)?$/';
                break;
            case 'timestamp':
            case 'datetime':// 2017-09-26 01:20:59
                $this->type = self::TYPE_DATETIME;
                $this->pattern = '/^[12][0-9]{3}-([0][0-9]|[1][0-2])-([0-2][0-9]|[3][01]) (([0-1][0-9]|[2][0-3])[:][0-5][0-9][:][0-5][0-9])?$/';
                break;
            case 'date':// 2017-09-26
                $this->type = self::TYPE_DATE;
                $this->pattern = '/^[12][0-9]{3}-([0][0-9]|[1][0-2])-([0-2][0-9]|[3][01])$/';
                break;
            case 'time':// 01:20:59
                $this->type = self::TYPE_TIME;
                $this->pattern = '/^([0-1][0-9]|[2][0-3])[:][0-5][0-9][:][0-5][0-9]$/';
                break;
            case 'blob':
                $this->type = self::TYPE_TEXT;
                break;
            default:
                $this->type = self::TYPE_VARCHAR;
                break;
        }
    }
    public function getTable(){
        return $this->table;
    }
    public function getName(){
        return $this->name;
    }
    public function getLabel(){
        return $this->label;
    }
    public function getDefault(){
        return $this->defaultValue;
    }
    public function getNativeType(){
        return $this->nativeType;
    }
    public function getPdoType(){
        return $this->pdoType;
    }
    public function getLen(){
        return $this->len;
    }
    public function getPrecision(){
        return $this->precision;
    }
    public function getType(){
        return $this->type;
    }
    public function getPattern(){
        return $this->pattern;
    }

    public function setDefault($value){
        $this->defaultValue = $value;
    }
    public function setLabel($label){
        $this->label = $label;
    }
    public function setType($type){
        $this->type = $type;
    }
    public function setPattern($pattern){
        $this->pattern = $pattern;
    }
    public function inRequest($inRequest = null){
        if(!is_null($inRequest))
            $this->inRequest = $inRequest;

        return $this->inRequest;
    }
    public function isRequired(){
        return $this->required;
    }
    public function setRequired($required = true){
        $this->required = $required;
    }
    public function validate($value = null,$strMessage = null){
        $strMessage = $strMessage?? "{label} is invalid!";

        $strMessage = str_replace("{table}", $this->getTable(),$strMessage);
        $strMessage = str_replace("{name}", $this->getName(),$strMessage);
        $strMessage = str_replace("{label}", $this->getLabel(),$strMessage);
        $strMessage = str_replace("{pattern}", $this->getPattern(),$strMessage);


        if ($this->isRequired() && empty($value)) {
            throw new \Exception($strMessage);
            return false;
        }

        $pattern = $this->getPattern();
        if (!is_null($pattern) && !preg_match($pattern, $value)) {
            throw new \Exception($strMessage);
            return false;
        }

        return true;
    }
}

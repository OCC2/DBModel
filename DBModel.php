<?php
/**
 * interface for catching children of DBModel
 */
interface IDBModelException{}
/**
 * Exception for all database models
 * @abstract
 * @author Milan Onderka
 * @category Exceptions
 * @package occ2
 * @version 1.0.0
 */
class DBModelException extends \Exception implements IDBModelException{}

/**
 * DBModel interface
 */
interface IDBModel{
    /**
     * @param \Nette\Database\Context $database
     * @param array $config
     */
    public function __construct(\Nette\Database\Context $database,$config=[]);
    
    /**
     * @param array $config
     */
    public function setConfig($config=[]);
    
    /**
     * @ return array
     */
    public function getConfig();
    
    /**
     * @param string $key
     * @param mixed $value
     */
    public function addConfig($key,$value);
    
    /**
     * @param string $primaryCol
     */
    public function setPrimary($primaryCol);
    
    /**
     * @return string
     */
    public function getPrimary();
    
    /**
     * @return \Nette\Database\Selection
     */
    public function getTable();
    
    /**
     * @param string $tableName
     */
    public function setTable($tableName);
    
    /**
     * @param string $className
     */
    public function setExceptionClass($className);
    
    public function getExceptionClass();
    
    /**
     * @param string $colName
     */
    public function setUnique($colName);
    
    public function getUnique();
    
    /**
     * @param int $id
     * @param boolean $toArray
     */
    public function loadItem($id,$toArray=true);
    
    /**
     * @param mixed $id
     * @param string $key
     * @param mixed $value
     */
    public function changeItem($id,$key,$value);
    
    /**
     * @param mixed $key
     * @param mixed $value
     */
    public function valueExists($key,$value);
    
    /**
     * @param mixed $data
     */
    public function saveItem($data);
    
    /**
     * @param mixed $id
     */
    public function deleteItem($id);
    
    /**
     * @param mixed $data
     */
    public function addItem($data);
    
    /**
     * @param mixed $data
     */
    public function updateItem($data);
    
    public function deleteAll();
}

/**
 * Parent for all database models
 * @author Milan Onderka
 * @category Models
 * @package occ2
 * @version 1.0.0
 */
abstract class DBModel extends \Nette\Object implements IDBModel{
    /**
     * container to database
     * @var \Nette\Database\Context
     */
    public $db;

    /**
     * @var array
     */
    public $config=array();

    /**
     * @var \Nette\Database\Table\Selection
     */
    public $table;
    
    /**
     * name of primary key collumn
     * @var string
     */
    public $primaryKey="id";
    
    /**
     * array of required unique collumns
     * @var array
     */
    public $uniqueCols;
    
    /**
     * name of exception class which is throw during fails
     * @var string
     */
    public $exceptionClass;

    /**
     * constructor for DI
     * @param \Nette\DI\Container $container
     * @param array $config
     * @return void
     */
    public function __construct(\Nette\Database\Context $database,$config=[]) {
        $this->db = $database;
        $this->config = $config;
        $this->exceptionClass = get_class($this) . "Exception";
        return;
    }

    /**
     * get configuration of model
     * @return array
     */
    public function getConfig(){
        return $this->config;
    }
    
    /**
     * set configuration
     * @param array $config
     * @return void
     */
    public function setConfig($config=[]){
        $this->config=$config;
        return;
    }
    
    /**
     * add value to config
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function addConfig($key, $value) {
        $this->config["key"]=$value;
        return;
    }

    /**
     * clone new Database table
     * @return \Nette\Database\Selection
     */
    public function getTable() {
        return clone $this->table;
    }

    /**
     * set table
     * @param string $tableName
     * @return \Nette\Database\Table\selection
     */
    public function setTable($tableName) {
        $this->table = $this->db->table($tableName);
        return $this->table;
    }
    
    /**
     * set another exception class if needed
     * @param string $className
     * @return \DBModel
     */
    public function setExceptionClass($className){
        $this->exceptionClass = $className;
        return $this;
    }
    
    /**
     * exception class name
     * @return string
     */
    public function getExceptionClass() {
        return $this->exceptionClass;
    }
    
    /**
     * set name of column which is primary key
     * @param string $primaryCol
     * @return \DBModel
     */
    public function setPrimary($primaryCol){
        $this->primaryKey=$primaryCol;
        return $this;
    }

    /**
     * get primary key column
     * @return string
     */
    public function getPrimary(){
        return $this->primaryKey;
    }
    
    /**
     * set collumn which must be unique during editing or inserting
     * @param string $colName
     * @return \DBModel
     */
    public function setUnique($colName){
        $this->uniqueCols[]=$colName;
        return $this;
    }
    
    /**
     * get unique cols
     * @return array
     */
    public function getUnique(){
        return $this->uniqueCols;
    }
    
    /**
     * load one row identified by id
     * @param mixed $id identifier
     * @param bool $toArray convert result to array - default true
     * @return mixed
     */
    public function loadItem($id,$toArray=true){
        $res = $this->getTable()
             ->where($this->primaryKey,$id)
             ->fetch();
        if($toArray==true){
            return $res->toArray();
        }
        else{
            return $res;
        }
    }
    
    /**
     * change one value in collum and rows
     * @param mixed $id
     * @param string $key
     * @param mixed $value
     * @return void
     */
    public function changeItem($id,$key,$value){
        $this->getTable()
             ->where($this->primaryKey,$id)
             ->update([$key=>$value]);
        return;
    }
    
    /**
     * check if value exists in table row specified by key
     * @param string $key
     * @param mixed $value
     * @return boolean
     */
    public function valueExists($key,$value){
        $num = $this->getTable()->where($key,$value)->count();
        if($num!=0){
            return true;
        }
        else{
            return false;
        }
    }
    
    /**
     * intelligently save row to table
     * automaticly identify new or edited item
     * if required check uniqueness of required items
     * primary collumn must be set !!
     * @param array $data
     * @throws Exception
     */
    public function saveItem($data){
        if($this->primaryKey==""){
            throw new $this->exceptionClass("base.model.dbmodel.unsetPrimaryKey");
        }
        // switch between new and edited item
        if(!isset($data[$this->primaryKey]) || $data[$this->primaryKey]==0 || $data[$this->primaryKey]==""){
            $this->addItem($data);
            return;
        }
        else{
            $this->updateItem($data);
            return;
        }
    }
    
    /**
     * delete item
     * @param mixed $id
     * @return void
     */
    public function deleteItem($id){
        $this->getTable()
             ->where($this->primaryKey,$id)
             ->delete();
        return;
    }
    
    /**
     * add item
     * @param array $data
     * @return void
     * @throws exception
     */
    public function addItem($data){
        // check unique items required
        if(count($this->uniqueCols)!=0){
            foreach ($this->uniqueCols as $requiredUniqueCol){ 
                if($this->valueExists($requiredUniqueCol, $data[$requiredUniqueCol])==true){
                    throw new $this->exceptionClass("base.model.dbmodel.add.collumnMustBeUnique");
                }
            }
        }
        $this->getTable()->insert($data);
        return;        
    }
    
    /**
     * update item
     * @param array $data
     * @return void
     * @throws exception
     */
    public function updateItem($data){
        // check unique items required
        if(is_array($this->uniqueCols)){
            foreach ($this->uniqueCols as $requiredUniqueCol) {
                // load old value of col
                $oldValue = $this->getTable()
                                 ->where($this->primaryKey,$data[$this->primaryKey])
                                 ->fetch()[$requiredUniqueCol];
                // check if is same
                if($oldValue!=$data[$requiredUniqueCol]){
                    if($this->valueExists($requiredUniqueCol, $data[$requiredUniqueCol])==true){
                        throw new $this->exceptionClass("base.model.dbmodel.update.collumnMustBeUnique");
                    }
                }
            }
        }   
        $this->getTable()->where($this->primaryKey,$data[$this->primaryKey])->update($data);
        return;        
    }
    
    /**
     * delete all items in table
     * @return void
     */
    public function deleteAll(){
        $this->getTable()->delete();
        return;
    }
    
    /**
     * create random string
     * @param int $length
     * @return string
     */
    public function randomString($length){
            return \Nette\Utils\Random::generate($length);

    }
}

<?php namespace Model;

use Lib\FuncCallable;
use Lib\ORM;

/**
 * model多少还是有用的，省的去翻数据库。。。
 * @method $this selectOne(array $columns = ['*'])
 * @method $this first(array $columns = ['*'])
 * @method $this select(array $columns = ['*'])
 */
class Kernel extends ORM implements \ArrayAccess, \JsonSerializable {
    // -----------------------------
    public static $tableName = '';
    public static $params    = [];
    // -----------------------------
    private $_data = [];

    public function __construct() {
//        var_dump(implode(':', [__FILE__, __CLASS__, __FUNCTION__,]));
        parent::__construct();
    }

    public static function __callStatic($name, $arguments) {
        $callStt = parent::__callStatic($name, $arguments);
//        var_dump(implode(':', [__FILE__, __CLASS__, __FUNCTION__, $name]));
//        var_dump(self::$tableName);
//        var_dump(static::$tableName);
        $callStt->_table(static::$tableName);
        return $callStt;
    }

    public function __call($name, $arguments) {
        var_dump(implode(':', [__FILE__, __CLASS__, __FUNCTION__, $name]));
        $callResult = parent::__call($name, $arguments);
        switch ($name) {
            case 'first':
            case 'selectOne':
                $instance     = new static();
                $instanceData = [];
                foreach (static::$params as $key) {
                    $instanceData[$key] = $callResult[$key] ?? null;
                }
                $instance->_data = $instanceData;
                return $instance;
                break;
            case 'select':
                $arr = [];
                foreach ($callResult as $row) {
                    $instance     = new static();
                    $instanceData = [];
                    foreach (static::$params as $key) {
                        $instanceData[$key] = $row[$key] ?? null;
                    }
                    $instance->_data = $instanceData;
                    $arr[]           = $instance;
                }
                return $arr;
                break;
            default:
                return $callResult;
        }
    }

    // -----------------------------

    public function __get($name) {
        return $this->_data[$name];
    }

    public function __set($name, $value) {
        $this->_data[$name] = $value;
    }

    public function __isset($name) {
        return isset($this->_data[$name]);
    }

    public function __unset($name) {
        unset($this->_data[$name]);
    }

    // -----------------------------

    public function offsetGet($offset) {
        return $this->_data[$offset];
    }

    public function offsetSet($offset, $value) {
        $this->_data[$offset] = $value;
    }

    public function offsetExists($offset) {
        return isset($this->_data[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->_data[$offset]);
    }

    // -----------------------------

    /*public function __serialize() {
        return $this->_data;
    }

    public function __unserialize(array $data) {
        $this->_data = $data;
    }*/

    public function jsonSerialize() {
        return $this->_data;
    }
}
<?php namespace Model;

use Lib\FuncCallable;
use Lib\ORM;

/**
 * model多少还是有用的，省的去翻数据库。。。
 *
 * @dev 但是想了一下 model 好像不能解决 join 的问题。。。
 * @dev 而且现在的实现是真tm丑，日后还得改
 * @dev 参数本打算支持 get_xxx_attribute 和 set_xxx_attribute ，但是各种情况都没有考虑全
 * @dev 所以目前 useGetter 和 useSetter 直接返回了
 *
 * @method $this selectOne(array $columns = ['*'])
 * @method $this first(array $columns = ['*'])
 * @method array select(array $columns = ['*'])
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
        $callStt = (new static())->_table(static::$tableName)->$name(...$arguments);
        //--------------------------------------
        /*$callStt = parent::__callStatic($name, $arguments);
//        var_dump(implode(':', [__FILE__, __CLASS__, __FUNCTION__, $name]));
//        var_dump(self::$tableName);
//        var_dump(static::$tableName);
        $callStt->_table(static::$tableName);*/
        return $callStt;
    }

    public function __call($name, $arguments) {
//        var_dump(implode(':', [__FILE__, __CLASS__, __FUNCTION__, $name]));
        $callResult = parent::__call($name, $arguments);
        switch ($name) {
            case 'first':
            case 'selectOne':
                if (empty($callResult)) {
                    return null;
                }
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
        return $this->useGetter($name);
    }

    public function __set($name, $value) {
        $this->_data[$name] = $this->useSetter($name, $value);
    }

    public function __isset($name) {
        return isset($this->_data[$name]);
    }

    public function __unset($name) {
        unset($this->_data[$name]);
    }

    // -----------------------------

    public function offsetGet($offset) {
        return $this->useGetter($offset);
    }

    public function offsetSet($offset, $value) {
        $this->_data[$offset] = $this->useSetter($offset, $value);
    }

    public function offsetExists($offset) {
        return isset($this->_data[$offset]);
    }

    public function offsetUnset($offset) {
        unset($this->_data[$offset]);
    }

    // -----------------------------

    public function __serialize() {
        //@dev 未测试
        return $this->_data;
    }

    public function __unserialize(array $data) {
        //@dev 未测试
        $this->_data = $data;
    }

    public function jsonSerialize() {
        return $this->_data;
    }

    protected function useGetter($key) {
        $data = $this->_data[$key] ?? null;
        /*$funcKey = "get_{$key}_attribute";
        if (method_exists(static::class, $funcKey)) {
//        if (is_callable([static::class, $funcKey])) {
            return $this->$funcKey($data);
        }*/
        return $data;
    }

    protected function useSetter($key, $value) {
        /*$funcKey = "set_{$key}_attribute";
        if (method_exists($this, $funcKey)) {
            return $this->$funcKey($value);
        }*/
        return $value;
    }

    /**
     * @return array
     */
    public function toArray() {
        return $this->_data;
    }
}
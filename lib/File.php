<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/2/24
 * Time: 11:27
 */

namespace Lib;

/**
 * @deprecated
 * 话说单独封装一个file到底图啥呢。。。。。。。。
 * 考虑到file_get_contents性能良好。。。算了不写了
 */
class File {

    private $path = '';
    private $mode = 'w+b';
    //
    private $resource = false;

    /**
     * w+b为读写+二进制模式
     */
    public function __construct($path, $mode = 'w+b', $lazy = true) {
        return $this;
    }

    public function __destruct() {
        return true;
    }

    public function open() {
        $this->resource = fopen($this->path, $this->mode);
        return $this;
    }

    public function read($start = 0, $end = PHP_INT_MAX) {
        if (!$this->resource) $this->open();

    }

    public function write($data) {
        return $this;
    }

    public function append($data) {
        return $this;
    }

    public function delete() {
        return $this;
    }

    public function __toString() {
        return $this->path;
    }

    /*public function __call($name, $arguments) {
        if (!function_exists($name)) return false;
        array_unshift($arguments, $this->path);
        return call_user_func_array($name, $arguments);
    }*/

    /*use FuncCallable;
    protected static $saveSelf = true;
    public static    $basePath = '';

    public function __construct() {
        self::$basePath = __DIR__ . '/../';
        var_dump(self::$basePath);
    }

    public function _write($path, $data, $absolute = false) {
        file_put_contents(
            $absolute ? trim($path, '/') : self::$basePath . trim($path, '/'),
            $data
        );
    }

    public function _writeLine($path, $data, $absolute = false) {
        file_put_contents(
            $absolute ? trim($path, '/') : self::$basePath . trim($path, '/'),
            $data . "\r\n",
            FILE_APPEND
        );
    }

    public function _read($path, $absolute = false) {
        file_get_contents(
            $absolute ? trim($path, '/') : self::$basePath . trim($path, '/')
        );
    }*/
}
<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/8/27
 * Time: 4:10
 */

namespace Lib;

/**
 * @method string path()
 * @method string method()
 * @method array query()
 * @method string url()
 * @method string domain()
 * @method string version()
 * @method array header()
 * @method array cookie()
 * @method array data() post + query
 * @method array file()
 *
 * @method  array dump()
 *
 * @property string path
 * @property string method
 * @property array query
 * @property string url
 * @property string domain
 * @property string version
 * @property array header
 * @property array cookie
 * @property array data post + query
 * @property array file
 *
 */
class Request implements \ArrayAccess {
    use FuncCallable;

    private static $init  = false;
    private static $_data = [
        'path'    => '',//when cli, use first param
        'method'  => '',//CLI GET POST .etc
        'query'   => [],//get only
        //web only
        'url'     => '',
        'domain'  => '',
        'version' => '',//@todo
        'header'  => [],
        'cookie'  => [],
        'session' => [],
        //
        'data'    => [],//post+get
        'file'    => [],
    ];

    public function __construct() {
        if (self::$init) return;
        //
        switch (PHP_SAPI) {
//        switch (php_sapi_name()) {
            case 'cli':
                self::$_data['method'] = 'CLI';
                break;
            default:
                self::$_data['method'] = $_SERVER['REQUEST_METHOD'];
                break;
        }
        //
        switch (self::$_data['method']) {
            case 'CLI':
                global $argv;
                $arguments = $argv;
                array_shift($arguments);
                //
                if (empty($arguments)) break;
                //
                self::$_data['path'] = $arguments[0];
                $data                = [];
                foreach ($arguments as $arg) {
                    if (stripos($arg, '--') === 0) {
                        $l = substr($arg, 2);
                        $p = stripos($l, '=');
                        if ($p) {
                            $data[substr($l, 0, $p)] = substr($l, $p + 1);
                        } else {
                            $data[$l] = true;
                        }
                        continue;
                    }
                    if (stripos($arg, '-') === 0) {
                        $l = substr($arg, 1);
                        $p = stripos($l, '=');
                        if ($p) {
                            $data[substr($l, 0, $p)] = substr($l, $p + 1);
                        } else {
                            $data[$l] = true;
                        }
                        continue;
                    }
                    $data[] = $arg;
                }
                self::$_data['query'] = $data;
                //
                break;
            default:
                self::$_data['query'] = $_GET;
                //
                self::$_data['url'] = GenFunc::getRequestUrl();
                if (!empty(self::$_data['url'])) {
                    $urlInfo               = parse_url(self::$_data['url']);
                    self::$_data['domain'] = isset($urlInfo['host']) ? $urlInfo['host'] : '';
                    self::$_data['path']   = isset($urlInfo['path']) ?
                        trim($urlInfo['path'], '/') : '';
                }
                if (!empty($_SERVER['PATH_INFO'])) {
                    self::$_data['path'] = trim($_SERVER['PATH_INFO'], '/');
                }
                self::$_data['header']  = getallheaders();
                self::$_data['cookie']  = $_COOKIE ?: [];
                self::$_data['session'] = [];
                //
                self::$_data['data'] = ($_POST ?: []) + self::$_data['query'];
                self::$_data['file'] = $_FILES ?: [];
                break;
        }
        self::$init = true;
        return;
    }

    // ------------------------------------------------------

    private function _path(): string {
        return self::$_data['path'];
    }

    private function _method(): string {
        return self::$_data['method'];
    }

    private function _query(): array {
        return self::$_data['query'];
    }

    private function _url(): string {
        return self::$_data['url'];
    }

    private function _version(): string {
        return self::$_data['version'];
    }

    private function _header(): array {
        return self::$_data['header'];
    }

    private function _cookie(): array {
        return self::$_data['cookie'];
    }

    private function _data(): array {
        return self::$_data['data'];
    }

    private function _file(): array {
        return self::$_data['file'];
    }

    private function _dump(): array {
        return self::$_data;
    }

    // ------------------------------------------------------

    public function offsetExists($offset) {
        return isset(self::$_data[$offset]);
    }

    public function offsetGet($offset) {
        if (empty(self::$_data[$offset])) return false;
        return self::$_data[$offset];
    }

    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            self::$_data[] = $value;
        } else {
            self::$_data[$offset] = $value;
        }
    }

    public function offsetUnset($offset) {
        unset(self::$_data[$offset]);
    }

    // ------------------------------------------------------

    public function __get($name) {
        return self::$_data[$name];
    }

    public function __set($name, $value) {
        self::$_data[$name] = $value;
    }

    public function __isset($name) {
        return !empty(self::$_data[$name]);
    }

    public function __unset($name) {
        self::$_data[$name] = null;
    }

    // ------------------------------------------------------
}
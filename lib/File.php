<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/2/24
 * Time: 11:27
 */

namespace Lib;

/**
 * @method write(string $path, string $data, bool $absolute = false)
 * @method writeLine(string $path, string $data, bool $absolute = false)
 * @method read(string $path, bool $absolute = false)
 */
class File {
    use FuncCallable;
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
    }
}
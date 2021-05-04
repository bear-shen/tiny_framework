<?php namespace Model;

use Lib\ORM;

/**
 * @property string $id
 * @property string $hash
 * @property string $type
 * @property string $size
 * @property string $time_create
 * @property string $time_update
 */
class File extends Kernel {
    public static $tableName = 'file';
    public static $params    = [
        'id',
        'hash',
        'type',
        'size',
        'time_create',
        'time_update',
    ];
}
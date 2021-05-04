<?php namespace Model;

use Lib\ORM;

/**
 * @property string $id
 * @property string $id_group
 * @property string $name
 * @property string $alt
 * @property string $description
 * @property string $status
 * @property string $time_create
 * @property string $time_update
 */
class Tag extends Kernel {
    public static $tableName = 'tag';
    public static $params    = [
        'id',
        'id_group',
        'name',
        'alt',
        'description',
        'status',
        'time_create',
        'time_update',
        '',
        '',
        '',
        '',
        '',
    ];
}
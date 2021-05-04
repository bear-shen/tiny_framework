<?php namespace Model;

use Lib\ORM;

/**
 * @property string $id
 * @property string $name
 * @property string $alt
 * @property string $description
 * @property string $sort
 * @property string $status
 * @property string $time_create
 * @property string $time_update
 */
class TagGroup extends Kernel {
    public static $tableName = 'tag_group';
    public static $params    = [
        'id',
        'name',
        'alt',
        'description',
        'sort',
        'status',
        'time_create',
        'time_update',
    ];
}
<?php namespace Model;

use Lib\ORM;

/**
 * @property string $id
 * @property string $id_parent
 * @property string $status
 * @property string $sort
 * @property string $is_file
 * @property string $name
 * @property string $description
 * @property string $id_cover
 * @property string $list_tag_id
 * @property string $list_node
 * @property string $index
 * @property string $time_create
 * @property string $time_update
 */
class Node extends Kernel {
    public static $tableName = 'node';
    public static $params    = [
        'id',
        'id_parent',
        'status',
        'sort',
        'is_file',
        'name',
        'description',
        'id_cover',
        'list_tag_id',
        'list_node',
        'index',
        'time_create',
        'time_update',
    ];
}
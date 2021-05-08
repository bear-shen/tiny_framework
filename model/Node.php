<?php namespace Model;

use Lib\ORM;

/**
 * @property string $id
 * @property string $id_parent
 * @property string $status
 * @property string $sort
 * @property string $is_file
 * @property string $is_favourite
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

    /**
     * 排序方法
     * @param string $sort
     * @return string[]
     */
    public static function availSort($sort = '') {
        $target = [];
        switch ($sort) {
            default:
            case 'id_asc':
                $target = ['id', 'asc'];
                break;
            case 'id_desc':
                $target = ['id', 'desc'];
                break;
            case 'name_asc':
                $target = ['name', 'asc'];
                break;
            case 'name_desc':
                $target = ['name', 'desc'];
                break;
            case 'crt_asc':
                $target = ['time_create', 'asc'];
                break;
            case 'crt_desc':
                $target = ['time_create', 'desc'];
                break;
            case 'upd_asc':
                $target = ['time_update', 'asc'];
                break;
            case 'upd_desc':
                $target = ['time_update', 'desc'];
                break;
        }
        return $target;
    }

    public static function availStatus($status) {
        $target = [];
        switch ($status) {
            default:
            case 'list':
                $target = ['!=', 0];
                break;
            case 'favourite':
                $target = ['=', 2];
                break;
            case 'recycle':
                $target = ['=', 0];
                break;
        }
        return $target;
    }
}
<?php namespace Model;

use Lib\ORM;

/**
 * @property string $id_node
 * @property string $id_file
 * @property string $status
 */
class AssocNodeFile extends Kernel {
    public static $tableName = 'assoc_node_file';
    public static $params    = [
        'id_node',
        'id_file',
        'status',
    ];
}
<?php namespace Model;

use Lib\ORM;

/**
 * @property string $id
 * @property string $name
 * @property string $description
 * @property string $admin
 * @property string $status
 * @property string $auth
 * @property string $time_create
 * @property string $time_update
 */
class UserGroup extends Kernel {
//    use ORM;
    public static $tableName = 'user';
    public static $params    = [
        'id',
        'name',
        'description',
        'admin',
        'status',
        'auth',
        'time_create',
        'time_update',
    ];
}
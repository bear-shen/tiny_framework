<?php namespace Model;

use Lib\ORM;

/**
 * @property string $id
 * @property string $id_group
 * @property string $name
 * @property string $description
 * @property string $mail
 * @property string $password
 * @property string $status
 * @property string $time_create
 * @property string $time_update
 */
class User extends Kernel {
//    use ORM;
    public static $tableName = 'user';
    public static $params    = [
        'id',
        'id_group',
        'name',
        'description',
        'mail',
        'password',
        'status',
        'time_create',
        'time_update',
    ];

    /*public function get_name_attribute($data) {
        return 'get_name_attribute';
    }

    public function set_name_attribute($value) {
        return 'set_name_attribute';
    }*/
}
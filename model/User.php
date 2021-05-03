<?php namespace Model;

use Lib\ORM;

class User extends Kernel {
//    use ORM;
    public static $tableName = 'user';
    public static $params    = [
        'id',
        'name',
    ];
}
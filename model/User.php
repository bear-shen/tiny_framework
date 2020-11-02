<?php namespace Model;

use Lib\DB;
use Lib\GenFunc;

class User {
    public static function findUser($signature) {
        $usr = DB::queryGetOne(
            'select * from user where name=:sign1 or email=:sign2',
            ['sign1' => $signature, 'sign2' => $signature]
        );
        if (empty($usr)) return false;
        return $usr;
    }

    public static function passMake($password) {
        $password = md5(md5($password));
        return $password;
    }
}
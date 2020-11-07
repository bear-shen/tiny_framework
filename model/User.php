<?php namespace Model;

use Lib\DB;
use Lib\GenFunc;

class User {
    /**
     * @param $signature
     * @return array|bool
     */
    public static function findUser($signature) {
        $usr = DB::queryGetOne(
            'select * from user where (name=:sign1 or mail=:sign2) and status=1',
            ['sign1' => $signature, 'sign2' => $signature]
        );
        if (empty($usr)) return false;
        return $usr;
    }

    public static function createUser($userData) {
        $userData = $userData + [
                'id_group' => '999',
                'name'     => '',
                'email'    => '',
                'password' => '',
                'status'   => 0,
            ];
        $ifDup    = DB::queryGetOne('select * from user where name=:name or mail=:mail');
        if ($ifDup) return 'duplicate account, username or mail address';
        DB::execute(
            'insert into user (id_group, name, mail, password, status) value (:id_group, :name, :mail, :password, :status)',
            $userData
        );
        $targetId = DB::lastInsertId();
        return intval($targetId);
    }

    public static function passMake($password) {
        $password = md5(md5($password));
        return $password;
    }
}
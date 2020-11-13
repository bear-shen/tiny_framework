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

    public static function getUser($uid) {
        $usr = DB::queryGetOne(
            'select * from user where id=:id and status=1',
            ['id' => $uid,]
        );
        if (empty($usr)) return false;
        return $usr;
    }

    public static function createUser($userData) {
        $userData = $userData + [
                'id_group' => '999',
                'name'     => '',
                'mail'     => '',
                'pass'     => '',
                'status'   => 0,
            ];
//        var_dump($userData);
        $ifDup = DB::queryGetOne('select * from user where name=:name or mail=:mail', [
            'name' => $userData['name'], 'mail' => $userData['mail']
        ]);
        if ($ifDup) return 'duplicate account, username or mail address';
        DB::execute(
            'insert into user (id_group, name, mail, password, status) value (:id_group, :name, :mail, :pass, :status)',
            $userData
        );
        $targetId = DB::lastInsertId();
        return intval($targetId);
    }

    public static function passMake($password) {
        $password = md5(md5($password));
        return $password;
    }

    public static function isAdmin($uid) {
        $uidArr = DB::queryGetOne('select id,id_group from user where id=:id and status=1;',
                                  ['id' => $uid]
        );
        if (empty($uidArr)) return false;
        $groupArr = DB::queryGetOne('select id,admin from user_group where id=:id and status=1;',
                                    ['id' => $uidArr['id_group']]
        );
        if (empty($groupArr)) return false;
        if (!$groupArr['admin']) return false;
        return true;
    }

    public static function listUser($page = 1, $name = '', $group = 0) {
        $query   = 'select * from user';
        $offset  = intval(max($page - 1, 0) * 100);
        $queryWd = [];
        if (!empty($name)) {
            $queryWd[] = [
                'str' => 'name like :name',
                'val' => ['name' => $name],
            ];
        }
        if (!empty($group)) {
            $queryWd[] = [
                'str' => 'id_group like :id_group',
                'val' => ['id_group' => $name],
            ];
        }
        $queryVal = [];
        if (!empty($queryWd)) {
            $query .= ' where ';
            for ($i1 = 0; $i1 < sizeof($queryWd); $i1++) {
                $queryVal += $queryWd[$i1]['val'];
                if ($i1 == sizeof($queryWd) - 1) $query .= ' and ';
                $query .= $queryWd[$i1]['str'];
            }
        }
        $query .= ' limit 100 offset ' . $offset;
        return DB::query($query);
    }
}
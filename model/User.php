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

    public static function listUser($page = 1, $name = '', $group = '') {
        $query   = 'select
    us.id, 
--    us.id_group,
    us.name,
    us.mail,
--    us.password,
    us.status,
    us.time_create,
    us.time_update,
    gr.id           as group_id,
    gr.name         as group_name,
    gr.description  as group_description,
    gr.admin        as group_admin
--    gr.status       as group_status,
--    gr.time_create  as group_time_create,
--    gr.time_update  as group_time_update
from user us left join `user_group` gr on us.id_group=gr.id';
        $offset  = intval(max($page - 1, 0) * 100);
        $queryWd = [];
        if (!empty($name)) {
            $queryWd[] = [
                'str' => 'us.name like :name',
                'val' => ['name' => '%' . $name . '%'],
            ];
        }
        if (!empty($group)) {
            $groupIdList = DB::query(
                'select id from user_group where name like :group_name',
                ['group_name' => '%' . $group . '%']
            );
            $groupIdList = array_column($groupIdList, 'id');
            $queryWd[]   = [
                'str'  => 'id_group in (:v)',
                'bath' => $groupIdList,
            ];
        }
        $caller   = [
        ];
        $queryVal = [];
        if (!empty($queryWd)) {
            $query .= ' where ';
            for ($i1 = 0; $i1 < sizeof($queryWd); $i1++) {
                if ($i1 != 0) $query .= ' and ';
                if (!empty($queryWd[$i1]['val'])) {
                    $queryVal += $queryWd[$i1]['val'];
                }
                if (!empty($queryWd[$i1]['bath'])) {
                    $caller[] = $queryWd[$i1]['bath'];
                }
                $query .= $queryWd[$i1]['str'];
            }
        }
        $query .= ' limit 100 offset ' . $offset;
        array_unshift($caller, $query, $queryVal);
//        var_dump($query);
//        var_dump($caller);
        return call_user_func_array(
            [DB::class, 'query'], $caller
        );
    }

    public static function modUser($uid, $mods = []) {
        $user = DB::queryGetOne('select id, id_group, name, mail, password, status from user where id=:uid', ['uid' => $uid]);
        if (empty($user)) return 'user not found';
        $allowKey = [
            'id_group',
            'name',
            'mail',
            'status',
        ];
        //
        $toMod = [];
        foreach ($allowKey as $key) {
            if (!isset($mods[$key])) continue;
            $toMod[$key] = $mods[$key];
        }
        //
        return true;
    }
}
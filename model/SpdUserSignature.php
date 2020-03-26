<?php namespace Model;


use Lib\DB;

class SpdUserSignature {


    public static function searchUserId($name, $idOnly = true) {
        $pdo      = DB::getPdo();
        $uidQuote = $pdo->quote($name);
        //union 写法性能严重低于拆开单独写，所以分开
        /*select id from spd_user_signature where
false 
or userid   like {$uidQuote}
or username like {$uidQuote}
or nickname like {$uidQuote}
or portrait like {$uidQuote}
;*/
        if ($idOnly) {
            $colStr = 'id';
        } else {
            $colStr = 'id,userid,username,nickname,portrait';
        }
        $uidCol    = [
            DB::query("select {$colStr} from spd_user_signature where userid   like {$uidQuote};"),
            DB::query("select {$colStr} from spd_user_signature where username like {$uidQuote};"),
            DB::query("select {$colStr} from spd_user_signature where nickname like {$uidQuote};"),
            DB::query("select {$colStr} from spd_user_signature where portrait like {$uidQuote};"),
        ];
        $uidCol[0] = array_column($uidCol[0], 'id');
        $uidCol[1] = array_column($uidCol[1], 'id');
        $uidCol[2] = array_column($uidCol[2], 'id');
        $uidCol[3] = array_column($uidCol[3], 'id');
        $uidList   = [];
        foreach ($uidCol as $uidSubLi) {
            foreach ($uidSubLi as $uid) {
                $uidList[$uid] = $uid;
            }
        }
        if (!$idOnly) {
            return $uidList;
//            return array_values($uidList);
        }
        $uidList = array_keys($uidList);
        return $uidList;
    }

    /**
     * 根据用户信息获取uid，没有的话会自动生成一个
     * @param string $signature 可能的用户标识
     * @return int
     */
    public static function getUserId($signature) {
        if (empty($signature)) return 0;
        $uid   = 0;
        $ifUid = DB::query('select * from spd_user_signature where 
userid=:uid1 or
username=:uid2 or
nickname=:uid3 or
portrait=:uid4 limit 1;', [
            'uid1' => $signature,
            'uid2' => $signature,
            'uid3' => $signature,
            'uid4' => $signature,
        ]);
        if (empty($ifUid)) {
            DB::query('insert into spd_user_signature 
(nickname, username, portrait, userid) 
VALUE 
(:userid,:username,:nickname,:portrait)', [
                'userid'   => $signature,
                'username' => $signature,
                'nickname' => $signature,
                'portrait' => $signature,
            ]);
            $uid = DB::lastInsertId();
        } else {
            $uid = $ifUid[0]['id'];
        }
        return $uid;
    }

    /**
     * 批量获取用户数据
     * @param $uidList array [1,2,3]
     * @return array [id=>[id,name,nickname,portrait,uid]]
     */
    public static function getUserSignatureList($uidList) {
        $userDataInDb = DB::query('select 
id,portrait,nickname,username,userid 
from spd_user_signature 
where id in (:v)', [], $uidList);
        $userDataList = [];
        foreach ($userDataInDb as $userData) {
            $userDataList[$userData['id']] = $userData;
        }
        return $userDataList;
    }

    /**
     * 返回一个用于显示的名字，做兼容处理的
     * @param $userSignature array ['username'=>'','nickname'=>'', ... ]
     * @return string
     */
    public static function loadTargetName($userSignature) {
        if (empty($userSignature)) return '';
        $targetUserName = '';
        if (!empty($userSignature['username'])) {
            $targetUserName = $userSignature['username'];
        } elseif (!empty($userSignature['nickname'])) {
            $targetUserName = $userSignature['nickname'];
        } elseif (!empty($userSignature['userid'])) {
            $targetUserName = $userSignature['userid'];
        } elseif (!empty($userSignature['userid'])) {
            $targetUserName = $userSignature['portrait'];
        }
        return $targetUserName;
    }
}
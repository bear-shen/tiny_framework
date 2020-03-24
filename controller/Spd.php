<?php namespace Controller;

use Lib\DB;
use Lib\GenFunc;
use Lib\Request;
use Lib\Response;
use Model\SpdOpMap;

class Spd extends Kernel {
    public function post_getAct() {
        $query    = Request::data() + [
                'title'    => '',
                'username' => '',
                'cid'      => '',
                'tid'      => '',
                'page'     => 1,
            ];
        $pdo      = DB::getPdo();
        $queryArr = [];
        if (!empty($query['title'])) {
            $queryArr[] = 'spt.title like ' . $pdo->quote($query['title']);
        }
        if (!empty($query['username'])) {
            $queryArr[] = '( ' .
                          'sus.username like ' . $pdo->quote($query['username']) . ' or ' .
                          'sus.nickname like ' . $pdo->quote($query['username']) .
                          ' )';
        }
        if (!empty($query['cid'])) {
            $queryArr[] = 'sp.cid = ' . $pdo->quote($query['cid']);
        }
        if (!empty($query['tid'])) {
            $queryArr[] = 'sp.tid = ' . $pdo->quote($query['tid']);
        }
        $queryStr = implode(' and ', $queryArr);
        if (!empty($queryStr)) $queryStr = 'where ' . $queryStr;
        $pageSet = 200;
        $offset  = ((intval($query['page']) ?: 1) - 1) * $pageSet;
//        DB::$logging = true;
        $result = DB::query("select 
 sp.id,
 sp.fid,sp.tid,sp.pid,sp.cid,
 sp.uid,sus.username,sus.userid,sus.nickname,sus.portrait,
 sp.index_p,sp.index_c,
 sp.time_pub,sp.time_scan,
 spt.title,
 spc.content
 from 
spd_post sp
left join spd_post_content spc on sp.cid=spc.cid
left join spd_user_signature sus on sp.uid=sus.id
left join spd_post_title spt on sp.tid=spt.tid and sp.index_p=1 and sp.index_c=0
{$queryStr}
order by sp.id desc
limit {$pageSet} offset {$offset}
;");
//        var_dump(DB::$log);
        return $this->apiRet($result);
    }

    public function post_operateAct() {
        $query = Request::data() + [
                'operate' => '',
                'id'      => '',
            ];

        if (empty($query['id'])) return $this->apiErr(1001, 'need post id');
        $operateVal = SpdOpMap::writeBinary('operate', $query['operate']);
//        var_dump($query['operate']);
//        var_dump($operateVal);
//        exit();
        //查重
        $ifDup = DB::query(
            'select * from spd_operate where post_id=:id and operate=:operate;',
            ['id' => $query['id'], 'operate' => $operateVal]
        );
        if (!empty($ifDup)) return $this->apiRet('duplicated');
        //写入
        DB::query('insert into spd_operate 
(post_id, operate, time_operate)
value (:post_id, :operate, :time_operate)', [
            'post_id'      => $query['id'],
            'operate'      => $operateVal,
            'time_operate' => date('Y-m-d H:i:s'),
        ]);
        $logId = DB::lastInsertId();
        DB::query('insert into spd_operate_content 
(id,operate_reason)
value (:id,:operate_reason)', [
            'id'             => $logId,
            'operate_reason' => '手工处理',
        ]);
        return $this->apiRet();
    }

    public function keyword_getAct() {
        $query   = Request::data() + [
                'value' => '',
                'name'  => '',
                'page'  => 1,
            ];
        $pageSet = 200;
        $offset  = ((intval($query['page']) ?: 1) - 1) * $pageSet;
        //
        DB::$logging = true;
        $pdo         = DB::getPdo();
        $queryStr    = [];
        if (!empty($query['value'])) {
            $queryStr[] = '`value` like ' . $pdo->quote($query['value']);
        }
        if (!empty($query['name'])) {
            $uidQuote   = $pdo->quote($query['name']);
            $uidList    = DB::query("select id from spd_user_signature where
false 
or userid   like {$uidQuote}
or username like {$uidQuote}
or nickname like {$uidQuote}
or portrait like {$uidQuote}
;");
            $uidList    = array_column($uidList, 'id');
            $queryStr[] = '`value` in (' . implode(',', $uidList) . ')';
        }
        $queryStr = implode(' and ', $queryStr);
        if (!empty($queryStr)) $queryStr = 'where ' . $queryStr;
        //
        $list = DB::query("select 
id,fid,operate,type,position,`value`,reason ,time_avail,status
from spd_keyword
{$queryStr}
order by id desc
limit {$pageSet} offset {$offset};");
        //
        $userInDB = [];
        foreach ($list as $item) {
            if ($item['position'] == 2)
                $userInDB[$item['value']] = 1;
        }
        $userDataList = $this->getUserSignatureList(
            array_keys($userInDB)
        );
        for ($i1 = 0; $i1 < sizeof($list); $i1++) {
            if ($list[$i1]['position'] == 2) {
                $list[$i1]['value'] = $this->loadTargetName(
                    isset($userDataList[$list[$i1]['value']]) ?
                        $userDataList[$list[$i1]['value']] : []
                );
            }
            $list[$i1]['operate']    = SpdOpMap::parseBinary('operate', $list[$i1]['operate']);
            $list[$i1]['operate']    = array_keys(array_filter($list[$i1]['operate']));
            $list[$i1]['type']       = SpdOpMap::parseBinary('type', $list[$i1]['type']);
            $list[$i1]['type']       = array_keys(array_filter($list[$i1]['type']));
            $list[$i1]['position']   = SpdOpMap::parseBinary('position', $list[$i1]['position']);
            $list[$i1]['position']   = array_keys(array_filter($list[$i1]['position']));
            $list[$i1]['time_avail'] = date('Y-m-d\TH:i:s', strtotime($list[$i1]['time_avail']));
        }
//        return $this->apiRet($keywordList,0,DB::$log);
        return $this->apiRet($list);
    }

    public function keyword_modifyAct() {
        $query = Request::data() + [
                'name' => '',
                'page' => 1,
            ];
        $query = GenFunc::array_only($query, [
            'id',
            'fid',
            'operate',
            'type',
            'position',
            'time_avail',
            'value',
            'reason',
            'status',
        ]);
        //
        $query['operate']  = explode(',', $query['operate']);
        $query['type']     = explode(',', $query['type']);
        $query['position'] = explode(',', $query['position']);
        //
        if (in_array('uid', $query['position'])) {
            $query['value'] = $this->getUserId($query['value']);
        }
        $query['operate']  = SpdOpMap::writeBinary('operate', $query['operate']);
        $query['type']     = SpdOpMap::writeBinary('type', $query['type']);
        $query['position'] = SpdOpMap::writeBinary('position', $query['position']);
        //
//        DB::$logging = true;
        if (empty($query['id'])) {
            unset($query['id']);
            $query['time_avail'] = date('Y-m-d H:i:s', strtotime($query['time_avail']));
            DB::query(
                'insert into spd_keyword
(fid, operate, type, position, value, reason, status, time_avail)  value 
(:fid, :operate, :type, :position, :value, :reason, :status, :time_avail);',
                $query
            );
        } else {
            DB::query('update spd_keyword set 
fid        =  :fid,
operate    =  :operate,
type       =  :type,
position   =  :position,
time_avail =  :time_avail,
"value"    =  :value,
reason     =  :reason,
status     =  :status
where id=:id', $query);
        }
        return $this->apiRet();
    }

    public function loop_getAct() {
        $query   = Request::data() + [
                'name' => '',
                'page' => 1,
            ];
        $pageSet = 200;
        $offset  = ((intval($query['page']) ?: 1) - 1) * $pageSet;
        //
        $list = DB::query("select id,fid,cid,uid,reason,time_loop,status from spd_looper

order by id desc
limit {$pageSet} offset {$offset};");

        //
        $userDataList = $this->getUserSignatureList(
            array_keys(array_flip(array_column($list, 'uid')))
        );
        for ($i1 = 0; $i1 < sizeof($list); $i1++) {
            $list[$i1]['value'] = $this->loadTargetName(
                isset($userDataList[$list[$i1]['value']]) ?
                    $userDataList[$list[$i1]['value']] : []
            );;
        }
        return $this->apiRet($list);
    }

    public function loop_modifyAct() {
    }

    /**
     * 根据用户信息获取uid，没有的话会自动生成一个
     */
    private function getUserId($signature) {
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
    private function getUserSignatureList($uidList) {
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
     * @param $userSignature array [1,2,3]
     * @return string
     */
    private function loadTargetName($userSignature) {
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
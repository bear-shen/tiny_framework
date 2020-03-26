<?php namespace Controller;

use Lib\DB;
use Lib\GenFunc;
use Lib\Request;
use Lib\Response;
use Model\SpdOpMap;
use Model\SpdUserSignature;

class Spd extends Kernel {
    public function post_getAct() {
        $query = Request::data() + [
                'title'    => '',
                'username' => '',
                'cid'      => '',
                'tid'      => '',
                'page'     => 1,
            ];
//        var_dump($query);
        $pdo      = DB::getPdo();
        $queryArr = [];
        if (!empty($query['title'])) {
            $queryArr[] = 'spt.title like ' . $pdo->quote($query['title']);
        }
        if (!empty($query['username'])) {
            $uidList    = SpdUserSignature::searchUserId($query['username']);
            $queryArr[] = 'sp.uid in (' . implode(',', $uidList) . ')';
        }
        if (!empty($query['cid'])) {
            $queryArr[] = 'sp.cid = ' . $pdo->quote($query['cid']);
        }
        if (!empty($query['tid'])) {
            $queryArr[] = 'sp.tid = ' . $pdo->quote($query['tid']);
        }
        $queryStr = implode(' and ', $queryArr);
//        var_dump($queryStr);
        if (!empty($queryStr)) $queryStr = 'where ' . $queryStr;
        $pageSet     = 200;
        $offset      = ((intval($query['page']) ?: 1) - 1) * $pageSet;
        DB::$logging = true;
        $result      = DB::query("select 
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
            $uidList    = SpdUserSignature::searchUserId($query['name']);
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
        $userDataList = SpdUserSignature::getUserSignatureList(
            array_keys($userInDB)
        );
        for ($i1 = 0; $i1 < sizeof($list); $i1++) {
            if ($list[$i1]['position'] == 2) {
                $list[$i1]['value'] = SpdUserSignature::loadTargetName(
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
                'id'         => '',
                'fid'        => 1,
                'operate'    => 6,
                'type'       => 1,
                'position'   => 2,
                'time_avail' => '2099-01-01T00:00:00',
                'value'      => '',
                'reason'     => '',
                'status'     => 1,
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
            $query['value'] = SpdUserSignature::getUserId($query['value']);
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
        DB::$logging = true;
        if (!empty($query['name'])) {
            $uidList    = SpdUserSignature::searchUserId($query['name']);
            $queryStr[] = '`value` in (' . implode(',', $uidList) . ')';
        }
        $queryStr = implode(' and ', $queryStr);
        if (!empty($queryStr)) $queryStr = 'where ' . $queryStr;
        //
        $list = DB::query(
            "select 
sl.id,sl.fid,sl.uid,sl.cid,
sus.nickname,sus.username,sus.portrait,sus.userid,
sl.reason,sl.status,sl.time_loop
from spd_looper sl
left join spd_user_signature sus on sl.uid=sus.id
{$queryStr}
order by id desc
limit {$pageSet} offset {$offset};");
        for ($i1 = 0; $i1 < sizeof($list); $i1++) {
            $list[$i1]['time_loop'] = date('Y-m-d\TH:i:s', strtotime($list[$i1]['time_loop']));
            $list[$i1]['username']  = SpdUserSignature::loadTargetName($list[$i1]);
            $list[$i1]              = GenFunc::array_only($list[$i1], [
                'id', 'fid', 'cid', 'username', 'reason', 'status', 'time_loop',
            ]);
        }
        return $this->apiRet($list);
    }

    public function loop_modifyAct() {
        $query = Request::data() + [
                'id'        => '',
                'fid'       => '',
                'username'  => '',
                'reason'    => '',
                'status'    => '',
                'time_loop' => '2099-01-01T00:00:00',
            ];
        $query = GenFunc::array_only($query, [
            'id',
            'fid',
            'username',
            'reason',
            'status',
            'time_loop',
        ]);
        //
        $query['uid'] = SpdUserSignature::getUserId($query['username']);
        unset($query['username']);
        //cid
        $postData     = DB::query("select cid from spd_post where uid =:uid order by id desc limit 1;");
        $query['cid'] = empty($postData[0]) ? 0 : $postData[0]['cid'];

//        DB::$logging = true;
        if (empty($query['id'])) {
            unset($query['id']);
            $query['time_avail'] = date('Y-m-d H:i:s', strtotime($query['time_avail']));
            DB::query(
                'insert into spd_looper
(fid, uid, cid, status, reason, time_loop)  value 
(:fid,:uid,:cid,:status,:reason,:time_loop);',
                $query
            );
        } else {
            DB::query('update spd_looper set 
uid        =  :uid,
cid        =  :cid,
status     =  :status,
reason     =  :reason,
time_loop  =  :time_loop
where id=:id', $query);
        }
        return $this->apiRet();
    }
}
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
        $query         = Request::data() + [
                'name' => '',
                'page' => 1,
            ];
        $pageSet       = 200;
        $offset        = ((intval($query['page']) ?: 1) - 1) * $pageSet;
        $keywordList   = DB::query("select 
id,fid,operate,type,position,`value`,reason ,time_avail,status
from spd_keyword
order by id desc
limit {$pageSet} offset {$offset};");
        $userInKeyword = [];
        foreach ($keywordList as $keyword) {
            if ($keyword['position'] == 2)
                $userInKeyword[$keyword['value']] = 1;
        }
        $userDataInDb = DB::query('select id,portrait,nickname,username,userid from spd_user_signature where id in (:v)', [], array_keys($userInKeyword));
        $userDataList = [];
        foreach ($userDataInDb as $userData) {
            $userDataList[$userData['id']] = $userData;
        }
        for ($i1 = 0; $i1 < sizeof($keywordList); $i1++) {
            if ($keywordList[$i1]['position'] == 2) {
                $targetUserName = '';
                if (isset($userDataList[$keywordList[$i1]['value']])) {
                    $userData = $userDataList[$keywordList[$i1]['value']];
                    if (!empty($userData['username'])) {
                        $targetUserName = $userData['username'];
                    } elseif (!empty($userData['nickname'])) {
                        $targetUserName = $userData['nickname'];
                    } elseif (!empty($userData['userid'])) {
                        $targetUserName = $userData['userid'];
                    }
                }
                $keywordList[$i1]['value'] = $targetUserName;
            }
            $keywordList[$i1]['operate']    = SpdOpMap::parseBinary('operate', $keywordList[$i1]['operate']);
            $keywordList[$i1]['operate']    = array_keys(array_filter($keywordList[$i1]['operate']));
            $keywordList[$i1]['type']       = SpdOpMap::parseBinary('type', $keywordList[$i1]['type']);
            $keywordList[$i1]['type']       = array_keys(array_filter($keywordList[$i1]['type']));
            $keywordList[$i1]['position']   = SpdOpMap::parseBinary('position', $keywordList[$i1]['position']);
            $keywordList[$i1]['position']   = array_keys(array_filter($keywordList[$i1]['position']));
            $keywordList[$i1]['time_avail'] = date('Y-m-d\TH:i:s', strtotime($keywordList[$i1]['time_avail']));
        }
        return $this->apiRet($keywordList);
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
            $ifUid = DB::query('select * from spd_user_signature where 
userid=:uid1 or
username=:uid2 or
nickname=:uid3 or
portrait=:uid4 limit 1;', [
                'uid1' => $query['value'],
                'uid2' => $query['value'],
                'uid3' => $query['value'],
                'uid4' => $query['value'],
            ]);
            if (empty($ifUid)) {
                DB::query('insert into spd_user_signature 
(nickname, username, portrait, userid) 
VALUE 
(:userid,:username,:nickname,:portrait)', [
                    'userid'   => $query['value'],
                    'username' => $query['value'],
                    'nickname' => $query['value'],
                    'portrait' => $query['value'],
                ]);
                $query['value'] = DB::lastInsertId();
            } else {
                $query['value'] = $ifUid[0]['id'];
            }
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
}
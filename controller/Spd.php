<?php namespace Controller;

use Lib\DB;
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
                          'sus.username like ' . $pdo->quote($query['username']).' or '.
                          'sus.nickname like ' . $pdo->quote($query['username']).
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

    public function post_modifyAct() {
        return '';
    }
}
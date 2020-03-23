<?php namespace Controller;

use Lib\DB;
use Lib\Request;
use Lib\Response;

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
            $queryArr[] = 'spt.title like ' . $pdo->quote('%' . $query['title'] . '%');
        }
        if (!empty($query['username'])) {
            $queryArr[] = 'sus.username like ' . $pdo->quote('%' . $query['username'] . '%');
        }
        if (!empty($query['cid'])) {
            $queryArr[] = 'sp.cid = ' . $pdo->quote($query['cid']);
        }
        if (!empty($query['tid'])) {
            $queryArr[] = 'sp.tid = ' . $pdo->quote($query['tid']);
        }
        $queryStr = implode(' and ', $queryArr);
        if (!empty($queryStr)) $queryStr = 'where ' . $queryStr;
        $pageSet     = 200;
        $offset      = ((intval($query['page']) ?: 1) - 1) * $pageSet;
//        DB::$logging = true;
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
left join spd_post_title spt on sp.tid=spt.tid and sp.index_p=1
{$queryStr}
limit {$pageSet} offset {$offset};");
//        var_dump(DB::$log);
        return $this->apiRet($result);
    }

    public function post_operateAct() {
        return '';
    }

    public function post_modifyAct() {
        return '';
    }
}
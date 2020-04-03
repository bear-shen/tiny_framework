<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/10/30
 * Time: 18:30
 */

namespace ControllerCli;

use Lib\DB;
use Lib\GenFunc;

class Transfer extends Kernel {

    /**
     * loaded
     */
    public function tranPostAct() {
        echo 'loading...' . "\r\n";
        $count = 0;
        $limit = 100000;
        DB::query('truncate tiebaspider_v3.spd_user_signature;');
        DB::query('truncate tiebaspider_v3.spd_post;');
        DB::query('truncate tiebaspider_v3.spd_post_title;');
        DB::query('truncate tiebaspider_v3.spd_post_content;');
        do {
            echo '--------------------------' . "\r\n";
            self::tick(true);
            echo '--------------------------' . "\r\n";
            $offset = $count * $limit;
            $res    = DB::query("select 
dbid,tid,pid,cid,is_lz,page_index,post_index,time_pub,time_scan,time_operate,user_name,title,content
 from tiebaspider_archive.post limit $limit offset $offset ;");
            if (empty($res)) {
                echo 'get empty post, end' . "\r\n";
                self::tick(true);
                return true;
            }
            echo 'round: ' . $count . ' loaded ' . sizeof($res) . ' rows, offset ' . $offset . "\r\n";
            self::tick();
//        var_dump($res);
            $data = [
                'post'  => [],
                'title' => [],
                'body'  => [],
            ];
            // ------------------------------------------------
            //获取内部用户id
            $uidList         = array_column($res, 'user_name');
            $recordedUidList = $this->getUidList($uidList);
//            var_dump($recordedUidList);
//            exit();
            // ------------------------------------------------
            echo 'tran post data:' . "\r\n";
            foreach ($res as $item) {
                $userName       = mb_trim($item['user_name']);
                $data['post'][] = [
                    'tid'        => (string)$item['tid'],
                    'pid'        => (string)$item['pid'],
                    'cid'        => (string)$item['cid'],
                    'uid'        => (string)(
                    empty($userName) ? 0 : $recordedUidList[$userName]
                    ),
                    'index_p'    => $item['page_index'],
                    'index_c'    => $item['post_index'],
                    'time_pub'   => $item['time_pub'],
                    'time_scan'  => $item['time_scan'],
                    'time_check' => $item['time_operate'],
                ];
                if (!empty($item['title'])) {
                    $data['title'][] = [
                        'tid'         => (string)$item['tid'],
                        'poster_name' => $item['user_name'],
                        'title'       => $item['title'],
                    ];
                }
                $data['body'][] = [
                    'cid'     => (string)$item['cid'],
                    'content' => $item['content'],
                ];
            }
            self::tick();
            echo 'post data traned, write post:' . "\r\n";
            DB::query('insert ignore into spd_post(:k) VALUES (:v);', [], $data['post']);
            self::tick();
            echo 'write title:' . "\r\n";
            DB::query('insert ignore into spd_post_title(:k) VALUES (:v);', [], $data['title']);
            self::tick();
            echo 'write content:' . "\r\n";
            DB::query('insert ignore into spd_post_content(:k) VALUES (:v);', [], $data['body']);
            self::tick();
            ++$count;
        } while (true);
        return true;
    }

    /**
     * 通过用户名获取用户id列表，不存在的用户名自动生成id
     * @param array $userNameList ['','','',]
     * @return array ['name'=>'id','name'=>'id','name'=>'id',]
     */
    private function getUidList($userNameList = []) {
        self::line('need user record:' . sizeof($userNameList));
        self::tick();
        $userNameList = array_values(array_filter(array_keys(array_flip($userNameList))));
        for ($i1 = 0; $i1 < sizeof($userNameList); $i1++) {
            $userNameList[$i1] = mb_trim($userNameList[$i1]);
        }
//        var_dump($userNameList);
//            DB::$logging=true;
        $uidListInDB     = DB::query('select id,username from tiebaspider_v3.spd_user_signature where username in (:v);', [], $userNameList);
        $recordedUidList = [];
        foreach ($uidListInDB as $item) {
            $recordedUidList[$item['username']] = $item['id'];
        }
        $newUidList = [];
        foreach ($userNameList as $item) {
            if (isset($recordedUidList[$item])) continue;
            $newUidList[] = [
                'username' => $item
            ];
        }
        self::line('new user record:' . sizeof($newUidList));
        self::line('exists user record:' . sizeof($recordedUidList));
        self::tick();
        if (!empty($newUidList)) {
//            var_dump($newUidList);
            $insUid            = DB::query('insert ignore into spd_user_signature(:k) VALUES (:v)', [], $newUidList);
            $appendUidListInDB = DB::query(
                'select id,username from tiebaspider_v3.spd_user_signature where username in (:v);',
                [],
                array_column($newUidList, 'username')
            );
            foreach ($appendUidListInDB as $item) {
                $recordedUidList[$item['username']] = $item['id'];
            }
        }
        self::line('total user record:' . sizeof($recordedUidList));
        self::tick();
//        exit();
        return $recordedUidList;
    }

    /**
     * loaded
     */
    public function tranLoopAct() {
        echo 'loading...' . "\r\n";
//        DB::query('insert into tiebaspider_v3.spd_looper
//(uid, cid, status, time_loop, time_create, time_update)
//SELECT pid,user_name,reason,group,status,time_add,time_operate
// from tiebaspider_archive.looper');
        echo 'making target' . "\r\n";
        DB::query('truncate tiebaspider_v3.spd_looper;');
        $resource = DB::query('select id,user_name,`group`,pid,reason,status,time_operate,time_add from tiebaspider_archive.looper');
        self::tick();
        $uidList = $this->getUidList(array_column($resource, 'user_name'));
        $target  = [];
        foreach ($resource as $row) {
            $userName = mb_trim($row['user_name']);
            if (empty($uidList[$userName])) continue;
            $target[] = [
                //'id'          => $row[''],
                'uid'       => $uidList[$userName],
                'cid'       => $row['pid'],
                'status'    => $row['status'],
                'reason'    => $row['group'] . ':' . $row['reason'],
                'time_loop' =>
                    empty($row['time_operate'])
                        ? $row['time_add']
                        : date('Y-m-d H:i:s', strtotime($row['time_operate']) + 1000 * 86400),
            ];
        }
        echo 'insert' . "\r\n";
        self::tick();
        DB::query('insert into tiebaspider_v3.spd_looper (uid, cid, status, reason, time_loop) values (:v);', [], $target);
        self::tick();
        return true;
    }

    /**
     * loaded
     */
    public function tranKeywordAct() {
        self::line('truncating');
        DB::query('truncate tiebaspider_v3.spd_keyword');
        DB::query('set autocommit = 0;');
        self::tick();
        self::line('writing');
        DB::query('insert into 
tiebaspider_v3.spd_keyword 
(id,fid, operate, type, position, value, reason, status, time_avail, time_create, time_update)  
select 
 id, 52, operate, type, position, `value`, description, status, \'2099-01-01 00:00:00\', time_add, time_mod
 from tiebaspider_archive.keyword
;');
        self::tick();
        //uid处理
        self::line('processing');
        $matchList = DB::query('select id,`value`,position from spd_keyword where position&2 <> 0');
        self::line('user to process:' . sizeof($matchList));
        $uidList = $this->getUidList(array_column($matchList, 'value'));
        self::line('process finished');
        foreach ($matchList as $i => $item) {
            if (!($i % 100)) {
                self::line('process:' . $i);
            }
            $userName = mb_trim($item['value']);
            if (empty($userName)) {
                self::line('empty after trim:' . $item['value']);
                self::line($item);
                continue;
            }
            if (empty($uidList[$userName])) {
                self::line('lost uid:' . $userName);
                self::line($item);
                exit();
            }
            DB::query(
                'update spd_keyword set `value`=:value , position=2 where id=:id;',
                [
                    'value' => $uidList[$userName],
                    'id'    => $item['id'],
                ]
            );
        }
        self::tick();
    }

    public function tranLogAct() {
        //创建临时表
        DB::query('truncate tiebaspider_v3.spd_operate_tmp;');
        DB::query('truncate tiebaspider_v3.spd_operate;');
        DB::query('truncate tiebaspider_v3.spd_operate_content;');
        $ts = DB::query('SHOW TABLE STATUS from tiebaspider_v3;');
//        DB::$logging = true;
        $incr = 0;
        foreach ($ts as $row) {
            if ($row['Name'] != 'spd_operate') continue;
            $incr = $row['Auto_increment'];
            break;
        }
        DB::query("create table if not exists tiebaspider_v3.spd_operate_tmp(
`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
`post_id` bigint(20) unsigned NOT NULL , 
`operate` tinyint(3) unsigned DEFAULT NULL,
`time_operate` datetime DEFAULT CURRENT_TIMESTAMP,
`time_execute` datetime DEFAULT CURRENT_TIMESTAMP,
`operate_id_list` text,
`operate_reason` text,
`execute_result` text,
  PRIMARY KEY (`id`)
) engine=Aria auto_increment=1;");
//        var_dump(DB::getPdo()->errorInfo());
        // auto_increment=${$incr}
        $count = 0;
        do {
            echo '--------------------------' . "\r\n";
            $offset = $count * 10000;
            $logs   = DB::query("select 
-- dbid_postdb,
-- tid,
-- pid,
cid,
pid,
operate,
time_operate,
time_execute,
operate_id,
operate_reason,
execute_result
-- user_name,
-- title,
-- content
 from tiebaspider_archive.log_operate 
 where operate != 1
 order by dbid asc
 limit 10000 offset {$offset} ;");
//            var_dump(DB::getPdo()->errorInfo());
            if (empty($logs)) {
                echo 'get empty log, end' . "\r\n";
                self::tick(true);
                break;
            }
            self::tick();
            echo 'round: ' . $count++ . ' loaded ' . sizeof($logs) . ' rows' . "\r\n";
            $cidList = array_column($logs, 'cid');
            $posts   = DB::query("select 
id,cid
 from tiebaspider_v3.spd_post sp
--  left join tiebaspider_v3.spd_post_content spc on sp.cid=spc.cid
--  left join tiebaspider_v3.spd_user su on sp.uid=su.id
  where cid in (:v);", [], $cidList);
//            var_dump(DB::getPdo()->errorInfo());
            $postsByCId = [];
            foreach ($posts as $item) {
                $postsByCId[$item['cid']] = $item['id'];
            }
            self::tick();
            echo 'getting data from post: ' . sizeof($postsByCId) . ' rows loaded ' . "\r\n";
//        $data = [
//            'log'     => [],
//            'content' => [],
//        ];
            //
            self::tick();
            echo 'make data' . "\r\n";
            $insTmpArr = [];
            foreach ($logs as $k => $item) {
                //不存在对应的post，跳过
                if (empty($postsByCId[$item['cid']])) {
                    echo 'Err: can\'t find cid :' . $item['cid'] . ' from post' . "\r\n";
                    continue;
                }
                $postId      = $postsByCId[$item['cid']];
                $insTmpArr[] = [
                    //'id'           => '',
                    'post_id'         => $postId,
                    'operate'         => $item['operate'],
                    'time_operate'    => $item['time_operate'],
                    'time_execute'    => $item['time_execute'],
                    'operate_id_list' => $item['operate_id'],
                    'operate_reason'  => $item['operate_reason'],
                    'execute_result'  => $item['execute_result'],
                ];
            }
            self::tick();
            echo 'insert tmp table' . "\r\n";
            DB::query('insert into tiebaspider_v3.spd_operate_tmp 
(post_id, operate, time_operate, time_execute, operate_id_list, operate_reason, execute_result) VALUES 
(:v)', [], $insTmpArr);
            echo 'insert tmp table finished' . "\r\n";
//            break;
        } while (true);
        DB::query('insert ignore into 
tiebaspider_v3.spd_operate 
(id, post_id, operate, time_operate, time_execute) 
select 
id, post_id, operate, time_operate, time_execute 
from spd_operate_tmp;');
//        var_dump(DB::getPdo()->errorInfo());
        DB::query('insert ignore into 
tiebaspider_v3.spd_operate_content 
(id, operate_id_list, operate_reason, execute_result) 
select 
id, operate_id_list, operate_reason, execute_result
from spd_operate_tmp
;');
        var_dump(DB::getPdo()->errorInfo());
//        var_dump(DB::$log);
        return true;
    }

}
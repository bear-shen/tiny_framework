<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/25
 * Time: 9:54
 */

namespace ControllerCli;

use Lib\DB;
use Lib\GenFunc;
use Swlib\Http\ContentType;
use Swlib\Saber;
use Swlib\SaberGM;

class Debug extends Kernel {
    public function tranLoopAct() {
        echo 'loading...' . "\r\n";
//        DB::query('insert into tiebaspider_v3.spd_looper
//(uid, cid, status, time_loop, time_create, time_update)
//SELECT pid,user_name,reason,group,status,time_add,time_operate
// from tiebaspider_v2.looper');
        self::tick();
        echo 'making target' . "\r\n";
        $resource           = DB::query('select * from tiebaspider_v2.looper');
        $target             = [];
        $targetUserNameList = [];
        foreach ($resource as $row) {
            $target[]             = [
                //'id'          => $row[''],
                'user_name'   => $row['user_name'],
                'uid'         => null,
                'cid'         => $row['pid'],
                'status'      => $row['status'],
                'reason'      => $row['group'] . ':' . $row['reason'],
                'time_loop'   => date('Y-m-d H:i:s', strtotime($row['time_add']) + 86400 * 1000),
                'time_create' => $row['time_add'],
                'time_update' => $row['time_operate'],
            ];
            $targetUserNameList[] = $row['user_name'];
        }
        self::tick();
        echo 'check exist user' . "\r\n";
        //获取用户ID
        $userListInDB    = DB::query('select su.id,su.username from spd_user su where username in (:v)', [], $targetUserNameList);
        $currentUserList = [];
        foreach ($userListInDB as $k) {
            $currentUserList[$k['username']] = $k['id'];
        }
        self::tick();
        echo 'write new user data' . "\r\n";
        //先写入新用户
        $newUserList = [];
        foreach ($targetUserNameList as $userName) {
            if (isset($currentUserList[$userName])) continue;
            $newUserList[] = [
                'username' => $userName,
            ];
        }
        DB::query('insert into spd_user (:k) values (:v)', [], $newUserList);
        //获取全部用户id
        self::tick();
        echo 'get all user data' . "\r\n";
        $userListInDB    = DB::query('select su.id,su.username from spd_user su where username in (:v)', [], $targetUserNameList);
        $currentUserList = [];
        foreach ($userListInDB as $k) {
            $currentUserList[$k['username']] = $k['id'];
        }
        for ($i1 = 0; $i1 < sizeof($target); $i1++) {
            $targetUid = $currentUserList[$target[$i1]['user_name']];
            unset($target[$i1]['user_name']);
            $target[$i1]['uid'] = $targetUid;
        }
        //
        self::tick();
        echo 'insert' . "\r\n";
        DB::query('insert into tiebaspider_v3.spd_looper (:k) values (:v);', [], $target);
        return true;
    }

    public function tranKeywordAct() {
        DB::query('insert into 
tiebaspider_v3.spd_keyword 
(operate, type, position, value, reason, status, delta, max_expire, time_avail, time_create, time_update) 
select 
 operate, type, position, `value`, description, status, 30, 180, date_add(now(),interval 365 * 5 day) , time_add, time_mod
 from tiebaspider_v2.keyword
;');
    }


    /**
     * 因为需要嵌入postid，所以应当在post导入之后跑
     * 否则会影响写入
     */
    public function tranLogAct() {
        echo 'loading...' . "\r\n";
        $count       = 0;
        DB::$logging = true;
        echo '--------------------------' . "\r\n";
        self::tick(true);
        self::tick();
        echo 'create tmp table' . "\r\n";
//        $incr += 1;
        DB::query("create temporary table if not exists tiebaspider_v3.spd_log_operate_tmp(
`id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
`post_id` bigint(20) unsigned NOT NULL , 
`tid` bigint(20) unsigned NOT NULL , 
`pid` bigint(20) unsigned NOT NULL , 
`cid` bigint(20) unsigned NOT NULL , 
`uid` bigint(20) unsigned NOT NULL , 
`is_lz` tinyint(1) unsigned DEFAULT '0',
`operate` tinyint(3) unsigned DEFAULT NULL,
`time_operate` datetime DEFAULT CURRENT_TIMESTAMP,
`time_execute` datetime DEFAULT CURRENT_TIMESTAMP,
`operate_id_list` text,
`operate_reason` text,
`execute_result` text,
  PRIMARY KEY (`id`)
) engine=Innodb auto_increment=1");
            DB::query('truncate tiebaspider_v3.spd_log_operate_tmp');
        do {
            echo '--------------------------' . "\r\n";
            $size = $count * 10000;
            $logs = DB::query("select 
-- dbid_postdb,
-- tid,
-- pid,
cid,
is_lz,
time_pub,
time_operate,
operate_id,
operate,
operate_reason,
time_execute,
execute_result
-- user_name,
-- title,
-- content
 from tiebaspider_v2.log_operate 
 where operate != 1
 limit 10000 offset {$size} ;");
            if (empty($logs)) {
                echo 'get empty log, end' . "\r\n";
                self::tick(true);
                return true;
            }
            self::tick();
            echo 'round: ' . $count++ . ' loaded ' . sizeof($logs) . ' rows' . "\r\n";
            $postCIdList = [];
            foreach ($logs as $item) {
                $postCIdList[] = $item['cid'];
            }
            $posts      = DB::query("select 
id,
tid,
pid,
cid,
uid,
index_p,
index_c,
is_lz,
time_pub,
time_scan,
time_operate
 from tiebaspider_v3.spd_post sp
--  left join tiebaspider_v3.spd_post_content spc on sp.cid=spc.cid
--  left join tiebaspider_v3.spd_user su on sp.uid=su.id
  where cid in (:v);", [], $postCIdList);
            $postsByCId = [];
            foreach ($posts as $item) {
                $postsByCId[$item['cid']] = $item;
            }
            self::tick();
            echo 'getting data from post: ' . sizeof($postsByCId) . ' rows loaded ' . "\r\n";
//        $data = [
//            'log'     => [],
//            'content' => [],
//        ];
            $ts   = DB::query('SHOW TABLE STATUS from tiebaspider_v3;');
            $incr = 0;
            foreach ($ts as $row) {
                if ($row['Name'] != 'spd_log_operate') continue;
                $incr = $row['Auto_increment'];
                break;
            }
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
                $post        = $postsByCId[$item['cid']];
                $insTmpArr[] = [
                    //'id'           => '',
                    'post_id'         => $post['id'],
                    'tid'             => $post['tid'],
                    'pid'             => $post['pid'],
                    'cid'             => $post['cid'],
                    'uid'             => $post['uid'],
                    'is_lz'           => $post['is_lz'],
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
            DB::query('insert ignore into 
tiebaspider_v3.spd_log_operate_tmp 
(:k) values (:v);', [], $insTmpArr);
        } while (true);

//            var_dump(DB::$pdo->errorCode());
//            var_dump(DB::$pdo->errorInfo());
//            exit();
        self::tick();
        echo 'insert spd_log_operate' . "\r\n";
        DB::query('insert ignore into tiebaspider_v3.spd_log_operate 
(id,post_id, tid, pid, cid, uid, is_lz, operate, time_operate, time_execute)
select id,post_id, tid, pid, cid, uid, is_lz, operate, time_operate, time_execute from tiebaspider_v3.spd_log_operate_tmp;
');
//            var_dump(DB::$pdo->errorCode());
//            var_dump(DB::$pdo->errorInfo());
        self::tick();
        echo 'insert spd_log_operate_content' . "\r\n";
        DB::query('insert ignore into tiebaspider_v3.spd_log_operate_content 
 (id, operate_id_list, operate_reason, execute_result) 
select id, operate_id_list, operate_reason, execute_result from tiebaspider_v3.spd_log_operate_tmp;
');
        self::tick();
        self::tick(true);
        return true;
    }

    public function tranPostAct() {
        echo 'loading...' . "\r\n";
        $count = 0;
        $limit = 10000;
        do {
            echo '--------------------------' . "\r\n";
            self::tick(true);
            echo '--------------------------' . "\r\n";
            $size = $count * $limit;
            $res  = DB::query("select 
dbid,tid,pid,cid,is_lz,page_index,post_index,time_pub,time_scan,time_operate,user_name,title,content
 from tiebaspider_v2.post limit $limit offset $size ;");
            if (empty($res)) {
                echo 'get empty post, end' . "\r\n";
                self::tick(true);
                return true;
            }
            self::tick();
            echo 'round: ' . $count++ . ' loaded ' . sizeof($res) . ' rows' . "\r\n";
//        var_dump($res);
            $data = [
                'post'  => [],
                'title' => [],
                'body'  => [],
            ];
            // ------------------------------------------------
            //获取内部用户id
            $uidList = [];
            foreach ($res as $item) {
                $uidList[] = $item['user_name'];
            }
            $uidList = array_values(array_filter(array_flip(array_flip($uidList))));
            self::tick();
            echo 'need user record:' . sizeof($uidList) . "\r\n";
//            DB::$logging=true;
            $uidListInDB     = DB::query('select * from spd_user where username in (:v) order by id desc', [], $uidList);
            $recordedUidList = [];
            foreach ($uidListInDB as $item) {
                $recordedUidList[$item['username']] = $item;
            }
//            var_dump(sizeof($recordedUidList));
//            var_dump(DB::$log);
//            exit();
            $newUidList = [];
            foreach ($uidList as $item) {
                if (isset($recordedUidList[$item])) continue;
                $newUidList[] = [
                    'username' => $item
                ];
            }
            self::tick();
            echo 'new user record:' . sizeof($newUidList) . "\r\n";
            echo 'exists user record:' . sizeof($recordedUidList) . "\r\n";
            if (!empty($newUidList)) {
                $insUid          = DB::query('insert ignore into spd_user(:k) VALUES (:v)', [], $newUidList);
                $uidListInDB     = DB::query('select * from spd_user where username in (:v)', [], $uidList);
                $recordedUidList = [];
                foreach ($uidListInDB as $item) {
                    $recordedUidList[$item['username']] = $item;
                }
            }
            self::tick();
            echo 'total user record:' . sizeof($recordedUidList) . "\r\n";
            // ------------------------------------------------
            echo 'tran post data:' . "\r\n";
            foreach ($res as $item) {
                $data['post'][] = [
                    'tid'          => (string)$item['tid'],
                    'pid'          => (string)$item['pid'],
                    'cid'          => (string)$item['cid'],
                    'uid'          => empty($item['user_name']) ? 0 : $recordedUidList[$item['user_name']]['id'],
                    'index_p'      => $item['page_index'],
                    'index_c'      => $item['post_index'],
                    'time_pub'     => $item['time_pub'],
                    'time_operate' => $item['time_operate'],
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
            DB::query('insert ignore into spd_post(:k) VALUES (:v)', [], $data['post']);
            self::tick();
            echo 'write title:' . "\r\n";
            DB::query('insert ignore into spd_post_title(:k) VALUES (:v)', [], $data['title']);
            self::tick();
            echo 'write content:' . "\r\n";
            DB::query('insert ignore into spd_post_content(:k) VALUES (:v)', [], $data['body']);
            self::tick();
        } while (true);
        return true;
    }

    public function emptyAct() {
//        var_dump(func_get_args());
//        var_dump('executed');
        return 'this is response' . "\r\n";
    }

    public function CurlAct() {
        $urlList   = [
            'https://www.baidu.com',
            'https://www.sohu.com',
            'https://www.sina.com.cn',
        ];
        $optList   = [
            CURLOPT_HEADER => 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/69.0.3497.100 Safari/537.36',
        ];
        $curlList  = [];
        $curlMulti = curl_multi_init();
        foreach ($urlList as $url) {
            $curl = curl_init($url);
            curl_setopt_array(
                $curl, $optList
            );
            curl_multi_add_handle($curlMulti, $curl);
        }
        $running = null;
        do {
            curl_multi_exec($curlMulti, $running);
        } while ($running > 0);

    }

    public function SwlibAct() {
        go(function () {
            /*$res = \Swlib\SaberGM::requests([['uri' => 'https://www.baidu.com']]);
            var_dump(get_class($res));
            var_dump(get_class($res[0]));
            var_dump($res[0]->getBody()->getSize());
            var_dump(get_class($res[0]->getBody()));
            var_dump((string)$res[0]->getBody());*/
            [$json,] = SaberGM::list([
                                         'uri' => [
                                             'http://httpbin.org/get',
                                         ]
                                     ]);
            var_dump((string)$json->getBody());
            var_dump(get_class($json));
            var_dump($json->getParsedJsonArray());
        });
    }

    public function ArrBenchmarkAct($a = 0, $b = 0) {
        self::line('debugger:benchmark', 2);
        $arr = [
            'a',
            'b',
            'c',
            'd',
            'e',
            'f',
            'g',
            'h',
            'i',
        ];

        $round = 100000;
        GenFunc::getTick();
        $target = [];
        for ($i1 = 0; $i1 < $round; $i1++) {
            $target = array_merge($arr);
        }
        unset($target);
        var_dump(GenFunc::getTick());
        $target = [];
        for ($i1 = 0; $i1 < $round; $i1++) {
            foreach ($arr as $item) {
                $target[] = $item;
            }
        }
        unset($target);
        var_dump(GenFunc::getTick());
    }
}
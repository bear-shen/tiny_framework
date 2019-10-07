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
    public function transDataAct() {
        echo 'loading...' . "\r\n";
        self::tick(true);
        $res = DB::query('select 
dbid,tid,pid,cid,is_lz,page_index,post_index,time_pub,time_scan,time_operate,user_name,title,content,text
 from tiebaspider_v2.post limit 10000;');
        echo 'loaded' . "\r\n";
        self::tick();
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
        $uidListInDB     = DB::query('select * from spd_user where username in (:v) order by id desc', $uidList);
        $recordedUidList = [];
        foreach ($uidListInDB as $item) {
            $recordedUidList[$item['username']] = $item;
        }
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
            $insUid          = DB::query('insert ignore into spd_user(:k) VALUES (:v)', $newUidList);
            $uidListInDB     = DB::query('select * from spd_user where username in (:v)', $uidList);
            $recordedUidList = [];
            foreach ($uidListInDB as $item) {
                $recordedUidList[$item['username']] = $item;
            }
        }
        self::tick();
        echo 'total user record:' . sizeof($recordedUidList) . "\r\n";
        // ------------------------------------------------
        echo 'tran post data:'  . "\r\n";
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
        echo 'post data traned, write post:'  . "\r\n";
        DB::query('insert ignore into spd_post(:k) VALUES (:v)', $data['post']);
        self::tick();
        echo 'write title:' . "\r\n";
        DB::query('insert ignore into spd_post_title(:k) VALUES (:v)', $data['title']);
        self::tick();
        echo 'write content:' . "\r\n";
        DB::query('insert ignore into spd_post_content(:k) VALUES (:v)', $data['body']);
        self::tick();
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
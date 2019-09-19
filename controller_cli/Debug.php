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
    public function emptyAct() {
        var_dump(func_get_args());
        var_dump('executed');
        return 'this is response'."\r\n";
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

    public function dbBenchmarkAct() {
        DB::query(
            'insert into sys_statistics (time_type, time_value, axis_y1, axis_y2, axis_y3, axis_x1) '
        );
    }
}
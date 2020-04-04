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
    public function dbAct() {
        DB::$logging = true;
        $result      = DB::query(
            "select * from test where para_1 in (:v) or para_2 in (:v) or para_3 in (:v)",
            [],
            [1, 2, 3],
            [1, 2, 3, 4],
            [8, 9, 10, 11, 12]
        );
        var_dump(DB::$log);
        var_dump($result);
    }

    public function emptyAct() {
    }

    public function trimAct() {
        /*self::tick();
        $time = 100000;
        for ($i1 = 0; $i1 < $time; $i1++) {
            mb_substr('asdasdasd', 0, 2, 'UTF-8');
        }
        self::tick();
        for ($i1 = 0; $i1 < $time; $i1++) {
            mb_substr('asdasdasd', 0, 2);
        }
        self::tick();
        for ($i1 = 0; $i1 < $time; $i1++) {
            substr('asdasdasd', 0, 2);
        }
        self::tick();
        //
        self::line('===============');
        for ($i1 = 0; $i1 < $time; $i1++) {
            mb_strlen('asdasdasd', 'UTF-8');
        }
        self::tick();
        for ($i1 = 0; $i1 < $time; $i1++) {
            mb_strlen('asdasdasd');
        }
        self::tick();
        for ($i1 = 0; $i1 < $time; $i1++) {
            strlen('asdasdasd');
        }
        self::tick();
        exit();*/
//        self::line(mb_explode('', '   asdasda
//        '));
//        self::line(mb_explode('', '   asdasda
//        0'));
//        self::line(explode('a', '   asdasda
//        '));
//        self::line(explode('a', '   asdaasda
//        a'));
        $str  = '0啊asd啊嗯啊啊啊asda0啊啊0';
        $deli = '0';
        self::line($str);
        self::line(mb_explode($deli, $str));
        self::line(implode($deli, mb_explode($deli, $str)));
//        self::line(mb_explode('', $str));
//        self::line(mb_explode('啊', $str, 2));
//        self::line(mb_explode('', $str));
//        self::line(mb_explode('', $str, 2));
        /*self::line(rtrim('   asdasda
        '));*/
//        var_dump(mb_trim('0asdaasda000'));
    }

    public function CurlDevAct() {
        $urlList = [
            'https://www.baidu.com',
            'https://www.sohu.com',
            'https://www.sina.com.cn',
        ];
//        $res=GenFunc::curlMulti($urlList,[
//            CURLOPT_HEADER => 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/69.0.3497.100 Safari/537.36',
//        ]);
//        $res=GenFunc::curl($urlList[0]);
//        $res=GenFunc::curl([CURLOPT_URL=>$urlList[0]]);
//        $res=GenFunc::curlMulti([[CURLOPT_URL=>$urlList[0]],[CURLOPT_URL=>$urlList[1]],[CURLOPT_URL=>$urlList[2]]]);
//        print_r($res);
        return [];

    }

    public function SwlibAct() {
        go(function () {
            /*$res = \Swlib\SaberGM::requests([['uri' => 'https://www.baidu.com']]);
            var_dump(get_class($res));



        $ch = curl_init($url);
        foreach ($config as $opt=>$val){
            curl_setopt($ch, $opt, $val);
        }
        $data=curl_exec($ch);
        curl_close($ch);dy()));
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
<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/25
 * Time: 9:54
 */

namespace ControllerCli;

use Lib\GenFunc;

class Debug extends Kernel {
    public function ArrBenchmark($a = 0, $b = 0) {
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
<?php namespace Lib;


trait CliHelper {

    public static function err($reason = '', $printTrace = false) {
        $trace = debug_backtrace();
//        var_dump($trace);
        $prevTrace = empty($trace[0]) ? [] : $trace[0];
        $str       = 'Err:';
        $str       .= empty($prevTrace) ? '' : $prevTrace['file'] . ':' . $prevTrace['line'] . ':';
        self::line($str . $reason);
        if ($printTrace) {
            print_r(debug_print_backtrace());
        }
        exit();
        return true;
    }

    /*public static function return($reason = '') {
        self::line($reason);
        exit();
        return true;
    }*/

    public static function tick($global = false) {
        $t = GenFunc::getTick($global);
        $m = GenFunc::memoryTick($global);
        self::line(($global ? 'global' . "\t" : '') . 'tick:' . "\t" . 'time:' . $t . "\t" . 'memory:' . $m);
    }

    public static function line($data = '', $level = 0) {
        $levelLen = 20;
        $padStr   = '=';
        $str      = '';
        //
        if (empty($level)) {
            $str = $data;
        } else {
            $str = ' ' . $data;
            $str = str_pad($str, $level * $levelLen, $padStr, STR_PAD_LEFT);
        }
        echo $str . "\n";
        return true;
    }

    public static function dump($data) {
        echo print_r($data, true);
        return true;
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/25
 * Time: 9:56
 */

namespace ControllerCli;


class Kernel {

    public static function route($route) {
        $routeList = [
            'scanner'  => ['class' => __NAMESPACE__ . '\Scanner', 'method' => 'scan',],
            'checker'  => ['class' => __NAMESPACE__ . '\Scanner', 'method' => 'check',],
            'operator' => ['class' => __NAMESPACE__ . '\Scanner', 'method' => 'operate',],
            'debug:'   => ['class' => __NAMESPACE__ . '\Debug',],
        ];
        //
        $className  = false;
        $methodName = false;
        //
        if (stripos($route['type'], ':')) {
            $path = explode(':', $route['type']);
            if (empty($routeList[$path[0] . ':'])) return self::err('route not found');
            $curRoute   = $routeList[$path[0] . ':'];
            $className  = $curRoute['class'];
            $methodName = ucfirst($path[1]);
        } else {
            if (empty($routeList[$route['type']])) return self::err('route not found');
            $curRoute = $routeList[$route['type']];
            //
            $className  = $curRoute['class'];
            $methodName = ucfirst($curRoute['method']);
        }
        if (!class_exists($curRoute['class'])) return self::err('class not found');
        $classObj=new $className();
        if (!method_exists($classObj, $methodName)) return self::err('method not found');
        self::line('route to:' . $className . '::' . $methodName);
        return call_user_func_array(
            [$classObj, $methodName],
            is_array($route['data']) ? $route['data'] : [$route['data']]
        );
    }

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

    public static function return($reason = '') {
        self::line($reason);
        exit();
        return true;
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
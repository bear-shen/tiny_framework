<?php namespace ControllerCli;

use Lib\CliHelper;

class Kernel {

    use CliHelper;

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
        $classObj = new $className();
        if (!method_exists($classObj, $methodName)) return self::err('method not found');
        self::line('route to:' . $className . '::' . $methodName);
        return call_user_func_array(
            [$classObj, $methodName],
            is_array($route['data']) ? $route['data'] : [$route['data']]
        );
    }
}
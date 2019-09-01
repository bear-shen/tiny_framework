<?php namespace ControllerCli;

use Lib\CliHelper;

class Kernel {

    public static $routeList = [
        /** ex.
         * 'namespace'=>[
         *  'route'=>['class'=>'','method'=>'',]
         * ]
         * 直接用::class的话ide会报错，实际上估计没有问题但是总之写好看点了，和laravel的group差不多意思
         */
    ];

    use CliHelper;

    /**
     * @param $route array
     *
     * ex.
     * [
     *      'type'=>'',
     *      'data'=>'',
     * ]
     *
     * @return  boolean|mixed
     */
    public static function route($route) {
        global $route_conf;
        if (!empty($route_conf['cli'])) {
            self::$routeList += $route_conf['cli'];
        }
        self::getRoute($route['type'], self::$routeList);
        //
        $routeData = [];
        //
        if (stripos($route['type'], ':') === false) {
            if (empty(self::$routeList[$route['type']])) return self::err('route not found');
        }


        //含:就根据:拆分 class:function
        //不含就直接走
        if (stripos($route['type'], ':')) {
            $path = explode(':', $route['type']);
            if (empty(self::$routeList[$path[0] . ':'])) return self::err('route not found');
            $curRoute   = self::$routeList[$path[0] . ':'];
            $className  = $curRoute['class'];
            $methodName = ucfirst($path[1]) . 'Act';
        } else {
            if (empty(self::$routeList[$route['type']])) return self::err('route not found');
            $curRoute = self::$routeList[$route['type']];
            //
            $className  = $curRoute['class'];
            $methodName = ucfirst($curRoute['method']) . 'Act';
        }
        //call
        if (!class_exists($className)) return self::err('class not found');
        $classObj = new $className();
        if (!method_exists($classObj, $methodName)) return self::err('method not found');
        self::line('route to:' . $className . '::' . $methodName);
        return call_user_func_array(
            [$classObj, $methodName],
            is_array($route['data']) ? $route['data'] : [$route['data']]
        );
    }

    /**
     * @param string|array $cur
     * @param array $routeList
     * @param bool $autoIndex
     * @return bool|array
     */
    private static function getRoute($cur, $routeList, $autoIndex = false) {
        if (is_string($cur)) {
            $cur = explode(':', $cur);
        }
        if (isset($routeList['namespace']) && isset($routeList['class'])) {
            if($autoIndex){

            }
            return $routeList;
        }
        $path = array_shift($cur);
        if (isset($routeList[$path])) {
            return self::getRoute($cur, $routeList, $autoIndex);
        }
        if (isset($routeList[$path . ':'])) {
            return self::getRoute($cur, $routeList, true);
        }
        return false;
    }
}
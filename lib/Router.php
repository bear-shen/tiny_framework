<?php namespace Lib;

/**
 * part
 * @method bool domain(string $path, \Closure $call, string $type = 'match|regex|suffix')
 * @method bool namespace(string $name, \Closure $call)
 * @method bool version(array | string $list, \Closure $call)
 * @method bool middleware(array | string $list, \Closure $call)
 * @method bool execute()
 *
 * final
 * @method bool get(string $path, \Closure | string | array $call, string $type = 'match')
 * @method bool post(string $path, \Closure | string | array $call, string $type = 'match')
 * @method bool any(string $path, \Closure | string | array $call, string $type = 'match')
 * @method bool cli(string $path, \Closure | string | array $call, string $type = 'match')
 * @method bool map(array $method, string $path, \Closure | string | array $call, string $type = 'match|regex|prefix')
 *
 * call的时候会传入正则匹配或者路由里的前后缀
 *
 * @example 1 嵌套
 * $router->namespace('dev', function () use ($router) {
 *      $router->version('v1', function () use ($router) {
 *          $router->cli('at/a', function () {
 *              var_dump('route execute');
 *          });
 *          $router->cli('at/b', function () {
 *              var_dump('route execute');
 *          });
 *      });
 *      $router->version('v2', function (Router $router) {
 *          $router->cli('at/c', function () {
 *              var_dump('route execute');
 *          });
 *          $router->cli('at/d', function () {
 *              var_dump('route execute');
 *          });
 *      });
 *      $router->cli('at/c', function () {
 *          var_dump('route execute');
 *      });
 * });
 *
 * @example 2 执行
 * $router->namespace('\ControllerCli', function (Router $router) {
 *      $router->cli('curl', ['Debug', 'CurlAct']);
 *      $router->cli('curl1', 'Debug@CurlAct');
 *      $router->cli('/curl_(.*)/i', function ($data):array {
 *          return 'return string';
 *      }, 'regex');
 *      $router->cli('curl-', function ($data):Response {
 *          return call_user_func_array([new \ControllerCli\Debug(), 'CurlAct'], func_get_args());
 *      }, 'prefix');
 *      $router->cli('curl-', function ($data):Response {
 *          return 'return string';
 *      }, 'prefix');
 * });
 * $router->execute($request);
 */
class Router {
    use FuncCallable;

    private $debugDump = false;

//    public static $counter = 0;
    //
    public static $_routeTable = [
        /*[
            'method'      => [],
            'domain'      => '',
            'domain_type' => '',//match|regex|suffix
            'path'        => '',
            'path_type'   => '',//match|regex|prefix
            'middleware'  => [],
            'version'     => [],
            'namespace'   => '',
            //'controller(@actAct)'
            //[controller,(actionAct)]
            //function($request){} note:only for regex and prefix
            'call'        => [],
        ]*/
    ];

    /** @var $current &array */
    public $current = false;

    public function __construct() {
        $this->newRoute();
    }

    //这边是写入路由表的部分，转接 setMethod 方法
    private function _get($path, $call, $type = 'match') {
        return $this->setMethod('GET', $path, $call, $type);
    }

    private function _post($path, $call, $type = 'match') {
        return $this->setMethod('POST', $path, $call, $type);
    }

    private function _any($path, $call, $type = 'match') {
        return $this->setMethod('ANY', $path, $call, $type);
    }

    private function _cli($path, $call, $type = 'match') {
        return $this->setMethod('CLI', $path, $call, $type);
    }

    private function _map($method, $path, $call, $type = 'match') {
        return $this->setMethod($method, $path, $call, $type);
    }

    //这边是回调的部分，进入路由 → 写入 → 调用下一级 → 恢复原始状态 → 进入下一个路由
    private function _domain($path, $call, $type = 'match') {
        $pre                          = $this->current;
        $this->current['domain']      = $path;
        $this->current['domain_type'] = $type;
        $call($this);
        $this->current = $pre;
        return true;
    }

    private function _namespace($name = '', $call) {
        $pre                        = $this->current;
        $this->current['namespace'] = $name;
        $call($this);
        $this->current = $pre;
        return true;
    }

    private function _version($list = [], $call) {
        $pre                      = $this->current;
        $this->current['version'] = is_string($list) ? [$list] : $list;
        $call($this);
        $this->current = $pre;
        return true;
    }

    //中间件
    private function _middleware($list = [], $call) {
        $pre                         = $this->current;
        $this->current['middleware'] = is_string($list) ? [$list] : $list;
        $call($this);
        $this->current = $pre;
        return true;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return bool
     */
    private function _execute() {
        if ($this->debugDump) {
            echo '<pre>';
            print_r(self::$_routeTable);
            print_r($this->current);
            print_r(Request::dump());
        }
        $targetRoute = false;
        $append      = [];
        foreach (self::$_routeTable as $route) {
//            var_dump(Request::path());
//            var_dump($route);
            $appendDomainInfo = [];
            $appendPathInfo   = [];
            //domain
            if (
                true
                && !empty($route['domain'])
            ) {
                if (empty(Request::domain())) continue;
                $hit = false;
                switch ($route['domain_type']) {
                    default:
                    case 'match':
                        if ($route['domain'] == Request::domain()) {
                            $hit = true;
                        }
                        break;
                    case 'regex':
                        $hit = preg_match($route['domain'], Request::domain(), $appendDomainInfo) ? true : false;
                        break;
                    case 'suffix':
                        $pos = stripos(Request::domain(), $route['domain']);
                        if ($pos === false) break;
                        if (strlen(Request::domain()) != $route['domain'] + $pos) break;
                        $hit              = true;
                        $appendDomainInfo = [substr(Request::domain(), 0, $pos)];
                        break;
                }
                if (!$hit) continue;
                if ($route['path_type'] == 'regex' && !empty($appendDomainInfo)) {
                    unset($appendDomainInfo[0]);
                    $appendDomainInfo = array_values($appendDomainInfo);
                }
            }
            //path
            if (
            true
            ) {
                $hit = false;
                switch ($route['path_type']) {
                    default:
                    case 'match':
                        if ($route['path'] == Request::path()) {
                            $hit = true;
                        }
                        break;
                    case 'regex':
                        $hit = preg_match($route['path'], Request::path(), $appendPathInfo) ? true : false;
//                        var_dump($appendPathInfo);
                        break;
                    case 'prefix':
                        $pos = stripos(Request::path(), $route['path']);
                        if ($pos === false) break;
                        if ($pos !== 0) break;
                        $hit            = true;
                        $appendPathInfo = [substr(Request::path(), strlen($route['path']))];
                        break;
                }
                if (!$hit) continue;
                if ($route['path_type'] == 'regex' && !empty($appendPathInfo)) {
                    unset($appendPathInfo[0]);
                    $appendPathInfo = array_values($appendPathInfo);
                }
            }
            //method
            if (
                true
                && !empty(Request::method())
                && !empty($route['method'])
                && !in_array('ANY', $route['method'])
            ) {
                if (!in_array(Request::method(), $route['method'])) continue;
            }
            //version
            if (
                true
                && !empty($route['version'])
            ) {
                if (empty(Request::version())) continue;
                if (!in_array(Request::version(), $route['version'])) continue;
            }
//            var_dump('hit');
            //
            $append      = array_merge($appendDomainInfo, $appendPathInfo);
            $targetRoute = $route;
            break;
        }
        if (!$targetRoute) {
            return $this->errorResponse('router not found', 404);
        }
        //middleware
        //这边想想其实根本不需要request，就目前的结构来说其实只需要处理他的回调值就可以了
        $request = new Request();
        foreach ($targetRoute['middleware'] as $middleware) {
            /**@var \Middleware\Base $middlewareObj */
            $middlewareObj = new $middleware();
            $response      = $middlewareObj->handle($request);
            if (!is_object($response) || get_class($response) != 'Lib\Request') {
                return $this->manualResponse($response);
            }
        }
        //get callable
        $called     = false;
        $className  = '';
        $actionName = '';
        switch (gettype($targetRoute['call'])) {
            case 'string':
                list($className, $actionName) = $this->getStrRoute($targetRoute['call']);
                break;
            case 'array':
                list($className, $actionName) = $this->getArrRoute($targetRoute['call']);
                break;
            case 'object':
                if (get_class($targetRoute['call']) != 'Closure') break;
                //这边还是和laravel一样只有string吧……
                $called     = true;
                $callResult = $targetRoute['call'](...$append);
                /*if (is_array($res)) {
                    list($className, $actionName) = $this->getArrRoute($res);
                } elseif (is_string($res)) {
                    list($className, $actionName) = $this->getStrRoute($res);
                } elseif (get_class($res) == Response::class) {
                }*/
                break;
        }
        if (!$called) {
            if (empty($className)) return $this->errorResponse('undefined class : ' . $className, 404);
            $class = $route['namespace'] . '\\' . $className;
            if (!class_exists($class)) return $this->errorResponse('class not found : ' . $class, 404);
            $class = new $class();
            if (!method_exists($class, $actionName)) return $this->errorResponse('method not found : ' . $class . '\\' . $actionName, 404);
            $callResult = call_user_func_array([$class, $actionName], $append);
        }
        Response::setContent($callResult);
        Response::execute();
        return true;
    }

    private function errorResponse($msg, $code = 100, $data = []) {
        Response::setContent(
            [
                'code' => $code,
                'msg'  => $msg,
                'data' => $data,
            ]
        );
        Response::execute();
        return true;
    }

    private function manualResponse($data) {
        Response::setContent($data);
        Response::execute();
        return true;
    }

    // ----------------------------------------------------------------
    //
    // ----------------------------------------------------------------

    private function getStrRoute($string = '') {
        $exp = explode('@', $string);
        if (empty($exp)) return [false, false];
        $className = $exp[0];
        if (sizeof($exp) == 1 || empty($exp[1])) {
            $actionName = 'indexAct';
        } else {
            $actionName = $exp[1];
        }
        return [$className, $actionName];
    }

    private function getArrRoute($array = []) {
        if (empty($array)) return [false, false];
        $className = $array[0];
        if (sizeof($array) == 1 || empty($array[1])) {
            $actionName = 'indexAct';
        } else {
            $actionName = $array[1];
        }
        return [$className, $actionName];
    }

    // ----------------------------------------------------------------
    //
    // ----------------------------------------------------------------

    /**
     * @return array
     */
    private function newRoute() {
//        ++self::$counter;
        $this->current = [
            'method'      => [],
            'domain'      => '',
            'domain_type' => '',
            'path'        => '',
            'path_type'   => '',
            'middleware'  => [],
            'version'     => [],
            'namespace'   => '',
            //            'class'       => '',
            //            'function'    => '',
            //            'view'        => '',
            'call'        => [],
            //            'count'       => self::$counter,
        ];
        return $this->current;
    }

    private function setMethod($method, $path, $call, $type = 'match') {
        $pre                        = $this->current;
        $this->current['method']    = is_string($method) ? [$method] : $method;
        $this->current['path']      = trim($path, '/');
        $this->current['path_type'] = $type;
        $this->current['call']      = $call;
        self::$_routeTable[]        = $this->current;
//        var_dump($this->current);
//        $this->newRoute();
        $this->current = $pre;
        return true;
    }
}
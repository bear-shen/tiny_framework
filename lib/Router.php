<?php namespace Lib;

/**
 * part
 * @method bool domain(string $path, \Closure $call, string $type = 'match|regex|suffix')
 * @method bool namespace(string $name, \Closure $call)
 * @method bool version(array | string $list, \Closure $call)
 * @method bool middleware(array | string $list, \Closure $call)
 * @method bool execute(Request $request, Response $response)
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

    //
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

    //
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

    //
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
    private function _execute(Request $request, Response $response) {
        if ($this->debugDump) {
            echo '<pre>';
            print_r(self::$_routeTable);
            print_r($this->current);
            print_r($request->dump());
        }
        $targetRoute = false;
        $append      = [];
        foreach (self::$_routeTable as $route) {
//            var_dump($request->path());
//            var_dump($route);
            $appendDomainInfo = [];
            $appendPathInfo   = [];
            //domain
            if (
                true
                && !empty($route['domain'])
            ) {
                if (empty($request->domain)) continue;
                $hit = false;
                switch ($route['domain_type']) {
                    default:
                    case 'match':
                        if ($route['domain'] == $request->domain) {
                            $hit = true;
                        }
                        break;
                    case 'regex':
                        $hit = preg_match($route['domain'], $request->domain, $appendDomainInfo) ? true : false;
                        break;
                    case 'suffix':
                        $pos = stripos($request->domain, $route['domain']);
                        if ($pos === false) break;
                        if (strlen($request->domain) != $route['domain'] + $pos) break;
                        $hit              = true;
                        $appendDomainInfo = [substr($request->domain, 0, $pos)];
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
                        if ($route['path'] == $request->path) {
                            $hit = true;
                        }
                        break;
                    case 'regex':
                        $hit = preg_match($route['path'], $request->path, $appendPathInfo) ? true : false;
//                        var_dump($appendPathInfo);
                        break;
                    case 'prefix':
                        $pos = stripos($request->path, $route['path']);
                        if ($pos === false) break;
                        if ($pos !== 0) break;
                        $hit            = true;
                        $appendPathInfo = [substr($request->path, strlen($route['path']))];
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
                && !empty($request->method)
                && !empty($route['method'])
                && !in_array('ANY', $route['method'])
            ) {
                if (!in_array($request->method, $route['method'])) continue;
            }
            //version
            if (
                true
                && !empty($route['version'])
            ) {
                if (empty($request->version)) continue;
                if (!in_array($request->version, $route['version'])) continue;
            }
//            var_dump('hit');
            //
            $append      = array_merge($appendDomainInfo, $appendPathInfo);
            $targetRoute = $route;
            break;
        }
        if (!$targetRoute) {
            return $this->errorResponse('router not found', 101);
        }
        //middleware
        foreach ($targetRoute['middleware'] as $middleware) {
            /**@var \Middleware\Base $cls */
            $cls     = new $middleware();
            $request = $cls->handle($request);
            if (gettype($request) != 'object') {
                return $this->errorResponse('middleware rejected by: ' . $middleware, 102, $request);
            }
            if (gettype($request) != 'Lib\Request') {
                return $this->errorResponse('middleware rejected by: ' . $middleware, 102, $request);
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
            if (empty($className)) return $this->errorResponse('undefined class : ' . $className, 103);
            $class = $route['namespace'] . '\\' . $className;
            if (!class_exists($class)) return $this->errorResponse('class not found : ' . $class, 104);
            $class = new $class();
            if (!method_exists($class, $actionName)) return $this->errorResponse('method not found : ' . $class . '\\' . $actionName, 105);
            $callResult = call_user_func_array([$class, $actionName], $append);
        }
        $response->setContent($callResult);
        $response->execute();
        return true;
    }

    private function errorResponse($msg, $code = 100, $data = []) {
        Response::setContent(
            json_encode(
                [
                    'code' => $code,
                    'msg'  => $msg,
                    'data' => $data,
                ])
        );
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
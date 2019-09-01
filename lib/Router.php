<?php namespace Lib;

/**
 * final
 * @method bool get(string $path, \Closure | string | array $call, string $type = 'match')
 * @method bool post(string $path, \Closure | string | array $call, string $type = 'match')
 * @method bool any(string $path, \Closure | string | array $call, string $type = 'match')
 * @method bool cli(string $path, \Closure | string | array $call, string $type = 'match')
 * @method bool map(array $method, string $path, \Closure | string | array $call, string $type = 'match')
 *
 * part
 * @method bool domain(string $path, \Closure $call, string $type = 'match')
 * @method bool namespace(string $name = '', \Closure $call)
 * @method bool version(array | string $list = [], \Closure $call)
 * @method bool middleware(array | string $list = [], \Closure $call)
 * @method bool execute(Request $request)
 */
class Router {
    use FuncCallable;

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
            //[controller,action]
            //[controller,'*'(.Act)]
            //function($request){}
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
        return $this->setMethod('get', $path, $call, $type);
    }

    private function _post($path, $call, $type = 'match') {
        return $this->setMethod('post', $path, $call, $type);
    }

    private function _any($path, $call, $type = 'match') {
        return $this->setMethod('any', $path, $call, $type);
    }

    private function _cli($path, $call, $type = 'match') {
        return $this->setMethod('cli', $path, $call, $type);
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

    private function _execute(Request $request) {
        $targetRoute = false;
        foreach (self::$_routeTable as $route) {
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
                        $hit = preg_match($route['domain'], $request->domain) ? true : false;
                        break;
                    case 'suffix':
                        $pos = stripos($request->domain, $route['domain']);
                        if ($pos === false) break;
                        if (strlen($request->domain) != $route['domain'] + $pos) break;
                        $hit = true;
                        break;
                }
                if (!$hit) continue;
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
                        $hit = preg_match($route['path'], $request->path) ? true : false;
                        break;
                    case 'prefix':
                        $pos = stripos($request->path, $route['path']);
                        if ($pos === false) break;
                        if ($pos !== 0) break;
                        $hit = true;
                        break;
                }
                if (!$hit) continue;
            }
            //method
            if (
                true
                && !empty($request->method)
                && !empty($route['method'])
                && !in_array($request->method, $route['method'])
            ) {
                continue;
            }
            //version
            if (
                true
                && !empty($route['version'])
            ) {
                if (empty($request->version)) continue;
                if (!in_array($request->version, $route['version'])) continue;
            }
            //
            $targetRoute = $route;
        }
        if (!$targetRoute) return false;
        //middleware
        foreach ($targetRoute['middleware'] as $middleware) {
            /**@var \Middleware\Base $cls */
            $cls = new $middleware();
            $cls->handle($request);
        }
        //
        $this->abort(500);
        switch (gettype($targetRoute['call'])) {
            case 'string':
                $exp = explode('@', $targetRoute['call']);
                if (sizeof($exp) == 1) {
                }
                $className = [0];
                break;
            case 'array':

                break;
            case 'object':
                if (get_class($targetRoute['call']) != 'Closure') break;
                break;
        }
        return true;
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
        $this->current['path']      = $path;
        $this->current['path_type'] = $type;
        $this->current['call']      = $call;
        self::$_routeTable[]        = $this->current;
//        var_dump($this->current);
//        $this->newRoute();
        $this->current = $pre;
        return true;
    }

    private function abort($code = 500, $txt = '') {
        var_dump(Request::$method);
        exit();
    }
}
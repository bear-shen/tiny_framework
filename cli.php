<?php
var_dump('====================== cli init ======================');
require_once __DIR__ . '/vendor/autoload.php';

/**
 * --type= -t= ''
 * --data= -d= {}
 *
 * t 参考 Kernel 里的路由
 */

use Lib\GenFunc;
use Lib\Router;
use Lib\Request;
use Lib\Response;

require_once __DIR__ . '/config.php';
GenFunc::getTick();
GenFunc::memoryTick();

// ------------------------------------------------------------------
$router = new Router();
$router->namespace('\ControllerCli', function (Router $router) {
    $router->cli('debug/', function ($data) {
        $class    = new \ControllerCli\Debug();
        $function = $data . 'Act';
        if (!method_exists($class, $function)) {
            return 'err:method '.$function.' not found' . "\r\n";
        }
        return call_user_func_array([$class, $function], func_get_args());
    }, 'prefix');
    $router->cli('curl1', 'Debug@emptyAct');
    $router->cli('curl', ['Debug', 'emptyAct']);
    $router->cli('/curl_(.*)/i', function ($data) {
        var_dump($data);
        return 'called here' . "\r\n";
    }, 'regex');
    $router->cli('curl-', function ($data) {
        return call_user_func_array([new \ControllerCli\Debug(), 'emptyAct'], func_get_args());
    }, 'prefix');
});
$execResult = $router->execute(new Request(), new Response());
if (!$execResult) {
    echo 'err:router not found' . "\r\n";
}


/**/

//Router::execute($request);
//Kernel::route($app['route']);




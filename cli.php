<?php
var_dump('====================== cli init ======================');

/**
 * --type= -t= ''
 * --data= -d= {}
 *
 * t 参考 Kernel 里的路由
 */

use Lib\GenFunc;
use Lib\DB;
use ControllerCli\Kernel;
use Lib\Router;
use Lib\Request;

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/vendor/autoload.php';
GenFunc::getTick();
GenFunc::memoryTick();

// ------------------------------------------------------------------
$request = new Request();
$router  = new Router();
/*$router->namespace('\ControllerCli', function (Router $router) {
    $router->cli('curl', ['Debug', 'CurlAct']);
    $router->cli('curl1', 'Debug@CurlAct');
    $router->cli('/curl_(.*)/i', function ($data) {
        return ['Debug', 'CurlAct'];
    }, 'regex');
    $router->cli('curl-', function ($data) {
        call_user_func_array([new \ControllerCli\Debug(), 'CurlAct'], func_get_args());
    }, 'prefix');
});
$router->execute($request);*/


/**/

//Router::execute($request);
//Kernel::route($app['route']);




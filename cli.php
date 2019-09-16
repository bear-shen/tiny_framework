<?php
var_dump('====================== cli init ======================');

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
require_once __DIR__ . '/vendor/autoload.php';
GenFunc::getTick();
GenFunc::memoryTick();

$cache=new Predis\Client(/*'tcp://127.0.0.1:6379'*/);


// ------------------------------------------------------------------
$router = new Router();

$router->namespace('\ControllerCli', function (Router $router) {
    $router->cli('curl', ['Debug', 'CurlAct']);
    $router->cli('curl1', 'Debug@CurlAct');
    $router->cli('/curl_(.*)/i', function ($data) {
        var_dump($data);
        return 'called here';
    }, 'regex');
    $router->cli('curl-', function ($data) {
        return call_user_func_array([new \ControllerCli\Debug(), 'CurlAct'], func_get_args());
    }, 'prefix');
});
$router->execute(new Request(),new Response());


/**/

//Router::execute($request);
//Kernel::route($app['route']);




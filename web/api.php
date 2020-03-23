<?php
//开启php.ini中的display_errors指令
ini_set('display_errors', 1);

//通过error_reporting()函数设置，输出所有级别的错误报告
error_reporting(E_ALL);

//var_dump('====================== cli init ======================');
require_once __DIR__ . '/../vendor/autoload.php';

use Lib\GenFunc;
use Lib\Router;
use Lib\Request;
use Lib\Response;

require_once __DIR__ . '/../config.php';
GenFunc::getTick();
GenFunc::memoryTick();
//var_dump($_SERVER);

// ------------------------------------------------------------------
$router = new Router();

$router->namespace('\Controller', function (Router $router) {
    $router->get('curl1', 'Debug@emptyAct');
    $router->any('curl', ['Debug', 'emptyAct']);
    $router->get('/curl_(.*)/i', function ($data) {
//        var_dump($data);
        return 'called here' . "\r\n";
    }, 'regex');
    $router->get('curl-', function ($data) {
        return call_user_func_array([new \ControllerCli\Debug(), 'emptyAct'], func_get_args());
    }, 'prefix');
    $router->any('spd/', function ($data) {
        $class    = new \Controller\Spd();
        $function = $data . 'Act';
        if (!method_exists($class, $function)) {
            $failed=true;
            return '{"code":200,"msg":"error","data":"method not found"}';
        }
        return call_user_func_array([$class, $function], func_get_args());
    }, 'prefix');
});
$execResult = $router->execute(new Request(), new Response());
if (!$execResult) {
    $failed = true;
    echo '{"code":100,"msg":"error","data":"router not found"}';
}


/**/

//Router::execute($request);
//Kernel::route($app['route']);




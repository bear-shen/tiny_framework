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

$failed=false;

// ------------------------------------------------------------------
$router = new Router();
$router->namespace('\ControllerCli', function (Router $router) {
    $router->cli('debug/', function ($data) {
        $class    = new \ControllerCli\Debug();
        $function = $data . 'Act';
        if (!method_exists($class, $function)) {
            $failed=true;
            return 'err:method '.$function.' not found' . "\r\n";
        }
        return call_user_func_array([$class, $function], func_get_args());
    }, 'prefix');
    $router->cli('encoder/image', ['Encoder','image']);
    $router->cli('encoder/video', ['Encoder','video']);
    $router->cli('encoder/audio', ['Encoder','audio']);
});
$execResult = $router->execute();
if (!$execResult) {
    $failed=true;
    echo 'err:router not found' . "\r\n";
}

if($failed){

}


/**/

//Router::execute($request);
//Kernel::route($app['route']);




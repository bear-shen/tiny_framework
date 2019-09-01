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
$router->cli('a1', function () {
    var_dump('a1 execute');
});
$router->cli('a2', function () {
    var_dump('a2 execute');
});
$router->cli('a3', ['call', 'asc']);
$router->cli('a4', ['call', '*']);
$router->execute($request);
exit();

$router->namespace('dev', function () use ($router) {
    /** @var \Lib\Router $router */
    $router->version('v1', function () use ($router) {
        /** @var \Lib\Router $router */
        $router->cli('at/a', function () {
            var_dump('route execute');
        });
        $router->cli('at/b', function () {
            var_dump('route execute');
        });
    });
    $router->version('v2', function ($router) {
        /** @var \Lib\Router $router */
        $router->cli('at/c', function () {
            var_dump('route execute');
        });
        $router->cli('at/d', function () {
            var_dump('route execute');
        });
    });
    $router->cli('at/c', function () {
        var_dump('route execute');
    });
});
Router::execute($request);
//Kernel::route($app['route']);




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

header('Access-Control-Allow-Origin: ' . WEB_ORIGIN);
header('Access-Control-Allow-Credentials: true');
// ------------------------------------------------------------------
$router = new Router();

$router->namespace('\Controller', function (Router $router) {
    $router->middleware(
        [
            \Middleware\ApiRequest::class,
            \Middleware\UseSession::class,
            \Middleware\UserAuth::class,
            \Middleware\AdminAuth::class,
        ], function (Router $router) {
        $router->any('user/mod', ['User', 'modAct']);
        //
        $router->any('user_group/list', ['UserGroup', 'listAct']);
        $router->any('user_group/mod', ['UserGroup', 'modAct']);
        $router->any('user_group/del', ['UserGroup', 'delAct']);
        $router->any('user_group/auth', ['UserGroup', 'authAct']);
        //
    });
    $router->middleware(
        [
            \Middleware\ApiRequest::class,
            \Middleware\UseSession::class,
            \Middleware\UserAuth::class
        ], function (Router $router) {
        $router->any('user/list', ['User', 'listAct']);
        $router->any('tag_group/list', ['TagGroup', 'listAct']);
        $router->any('tag_group/mod', ['TagGroup', 'modAct']);
        $router->any('tag_group/del', ['TagGroup', 'delAct']);
        $router->any('tag/list', ['Tag', 'listAct']);
        $router->any('tag/mod', ['Tag', 'modAct']);
        $router->any('tag/del', ['Tag', 'delAct']);
    });
    $router->middleware(
        [
            \Middleware\ApiRequest::class,
            \Middleware\UseSession::class,
        ], function (Router $router) {
        $router->any('user/register', ['User', 'registerAct']);
        $router->any('user/login', ['User', 'loginAct']);
        //
        $router->any('file/list', ['File', 'listAct']);
        $router->any('file/mod', ['File', 'modAct']);
    });
    $router->middleware(
        [
            \Middleware\UseSession::class,
        ], function (Router $router) {
        $router->get('user/captcha', ['User', 'captchaAct']);
    });
    $router->get('dev', ['Debug', 'emptyAct']);
//    $router->any('/file/clear', ['Upload', 'clearAct']);

    //
    /*$router->post('upload/receive', 'Upload@receiveAct');
    //
    $router->get('curl1', 'Debug@emptyAct');
    $router->any('curl', ['Debug', 'emptyAct']);
    $router->get('/curl_(.*)/i', function ($data) {
//        var_dump($data);
        return 'called here' . "\r\n";
    }, 'regex');
    $router->get('curl-', function ($data) {
        return call_user_func_array([new \ControllerCli\Debug(), 'emptyAct'], func_get_args());
    }, 'prefix');*/
});


Response::setHeader('Access-Control-Allow-Origin: ' . WEB_ORIGIN);
Response::setHeader('Access-Control-Allow-Credentials: true');
$execResult = Router::execute();
if (!$execResult) {
    $failed = true;
    echo '{"code":101,"msg":"error","data":"router error occur"}';
}


/**/

//Router::execute($request);
//Kernel::route($app['route']);




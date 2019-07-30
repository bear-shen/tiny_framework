<?php
var_dump('====================== cli init ======================');

/**
 * --type= -t= {}
 * --data= -d= {}
 */

use Lib\GenFunc;
use Lib\DB;
use ControllerCli\Kernel as K;

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/vendor/autoload.php';
GenFunc::getTick();
GenFunc::memoryTick();

// ------------------------------------------------------------------

$options = getopt('t:d:', ['type:', 'data:']);
$options += ['t' => '', 'd' => '', 'type' => '', 'data' => ''];
$app     = [
    'method' => 'cli',
    'route'  => [
        'type' => $options['type'] ?
            $options['type'] : $options['t'] ?
                $options['t'] : $argv[1],
        'data' => $options['data'] ?
            $options['data'] : $options['d'] ?
                $options['d'] : array_slice($argv, 2),
    ]];
//var_dump($app);
GenFunc::getTick();
if (empty($app['route']['type'])) {
    \Lib\CliHelper::err('function not found');
}
K::route($app['route']);




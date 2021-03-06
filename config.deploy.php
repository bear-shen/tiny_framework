<?php
global $dbConf;
global $conf;
global $cache;
//数据库配置项独立，通用的配置项直接取，其他的extra通过Settings取
$dbConf    = [
    'host'    => '127.0.0.1',
    'db'      => 'database',
    'name'    => 'root',
    'pass'    => 'root',
    'charset' => 'utf8mb4',
];
define('BASE_PATH', __DIR__);
$conf      = [
    'base'    => [
        //'path' => __DIR__,
    ],
    'session' => [
        'key'    => 'ses_id',
        'prefix' => 'frm:session:',
        'expire' => 86400 * 180,
    ],
];
//缓存
$cache = new \Predis\Client(/*'tcp://127.0.0.1:6379'*/);
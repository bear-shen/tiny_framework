<?php
global $dbConf;
global $conf;
global $extraConf;
global $cache;
//数据库配置项独立，通用的配置项直接取，其他的extra通过Settings取
$dbConf    = [
    'host'    => '127.0.0.1',
    'db'      => 'tiebaspider_v3',
    'name'    => 'root',
    'pass'    => 'root',
    'charset' => 'utf8mb4',
];
$conf      = [
    'base'    => [
        'path' => __DIR__,
    ],
    'session' => [
        'key'    => 'ses_id',
        'prefix' => 'frm:session:',
        'expire' => 86400 * 180,
    ],
];
$extraConf = [
    'tieba_conf' => [
        [
            'name'          => '',
            'kw'            => '',
            'fid'           => '',
            'user'          => '',
            'cookie'        => '',
            'scan'          => true,
            'operate'       => true,
            'loop_day'      => 1000,
            'forbid_reason' => '',
        ],
    ]];
//缓存
$cache = new \Predis\Client(/*'tcp://127.0.0.1:6379'*/);
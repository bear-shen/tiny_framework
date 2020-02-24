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
            'name'    => '火星笔记本',
            'kw'      => '%e7%81%ab%e6%98%9f%e7%ac%94%e8%ae%b0%e6%9c%ac',
            'fid'     => '10087515',
            'user'    => 'nted_shen',
            'cookie'  => '',
            'scan'    => true,
            'operate' => true,
        ],
        [
            'name'    => '笔记本',
            'kw'      => '%e7%ac%94%e8%ae%b0%e6%9c%ac',
            'fid'     => '52',
            'user'    => 'nted_shen',
            'cookie'  => '',
            'scan'    => true,
            'operate' => false,
        ],
    ]];
//缓存
$cache = new \Predis\Client(/*'tcp://127.0.0.1:6379'*/);
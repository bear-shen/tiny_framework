<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/6/23
 * Time: 11:36
 */
use Lib\GenFunc;
global $app;
$app = [
    'method' => 'web',
    'route'  => [
        'uri'         => $_SERVER['REQUEST_URI'],
        'method'      => $_SERVER['REQUEST_METHOD'],
        'server_name' => $_SERVER['SERVER_NAME'],
    ]];
<?php

use Lib\GenFunc;
global $app;
$app = [
    'method' => 'web',
    'route'  => [
        'uri'         => $_SERVER['REQUEST_URI'],
        'method'      => $_SERVER['REQUEST_METHOD'],
        'server_name' => $_SERVER['SERVER_NAME'],
    ]];
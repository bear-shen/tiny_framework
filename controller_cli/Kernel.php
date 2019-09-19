<?php namespace ControllerCli;

use Lib\CliHelper;

class Kernel {

    public static $routeList = [
        /** ex.
         * 'namespace'=>[
         *  'route'=>['class'=>'','method'=>'',]
         * ]
         * 直接用::class的话ide会报错，实际上估计没有问题但是总之写好看点了，和laravel的group差不多意思
         */
    ];

    use CliHelper;
}
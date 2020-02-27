<?php namespace Model;


class SysStats extends Kernel {
    private static $table = 'sys_stat';

    public $timeType = [
        'second',
        'minute',
        'hour',
        'day',
        'week',
        'month',
        'season',
        'year',
        'global',
    ];

    public function increment() {

    }
}
<?php namespace Model;


class SysStats {
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
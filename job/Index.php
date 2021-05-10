<?php namespace Job;

use Lib\DB;
use Lib\GenFunc;
use Lib\Request;
use Model\File;

class Index {
    public function handle($data) {
        $query = Request::query();
        if (empty($query[1])) return 'no node id';
    }
}
<?php namespace Job;

use Lib\DB;
use Lib\GenFunc;
use Lib\Request;
use Model\File;

class Encoder {
    public function handle($data) {
        $query = Request::query();
        if (empty($query[1])) return 'no file id';
        $fileData = File::where('id', $query[1])->selectOne();
    }

    public function image() {
    }

    public function video() {
    }

    public function audio() {
    }
}
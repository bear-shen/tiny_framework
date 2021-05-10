<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/7/25
 * Time: 9:54
 */

namespace ControllerCli;

use Lib\DB;
use Lib\GenFunc;
use Lib\Request;
use Model\File;

class Encoder extends Kernel {
    public function image() {
        $query = Request::query();
        if (empty($query[1])) return 'no file id';
        $fileData = File::where('id', $query[1])->selectOne();
        return $fileData;
    }

    public function video() {
        $query = Request::query();
        if (empty($query[1])) return 'no file id';
        $fileData = File::where('id', $query[1])->selectOne();
        return $fileData;
    }

    public function audio() {
        $query = Request::query();
        if (empty($query[1])) return 'no file id';
        $fileData = File::where('id', $query[1])->selectOne();
        return $fileData;
    }
}
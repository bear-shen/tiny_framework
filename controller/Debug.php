<?php namespace Controller;

use Lib\DB;
use Lib\Request;
use Lib\Response;

class Debug extends Kernel {
    public function emptyAct() {
//        var_dump(func_get_args());
//        var_dump('executed');
//        Response::setHeader('Accept: javascript/json');
//        Response::setCookie(['name'=>'zzz','value'=>'z11']);
        DB::where('id', '123')->
        where(function ($query) {
//            var_dump($query);
            $query->where('status', 1)->where('name', 'hentai');
            var_dump($query);
        })->
        where([['admin', false], ['extra', true]])->
        first();
        return 'this is response' . "\r\n";
    }

    public function uploadAct() {
        $post = Request::post();
        $file = Request::file();
    }
}
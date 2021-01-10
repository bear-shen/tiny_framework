<?php namespace Controller;

use Lib\DB;
use Lib\ORM;
use Lib\Request;
use Lib\Response;

class Debug extends Kernel {
    public function emptyAct() {
//        var_dump(func_get_args());
//        var_dump('executed');
//        Response::setHeader('Accept: javascript/json');
//        Response::setCookie(['name'=>'zzz','value'=>'z11']);
        /*ORM::table('hentai')->
        leftJoin('user', 'name', 'user_name')->
        where('id', '123')->
        where(function ($query) {
//            var_dump($query);
            $query->where('status', 1)
                  ->where(function ($query) {
                      $query->where('neko', 'cannon');
                  })
                  ->where('name', 'hentai');;
        })->
        orWhere([['admin', false], ['partition', null], ['extra', true]])->
        where('time_create', '>', '1919-08-10 11:45:14')->
        orWhereRaw('raw+1=0')->
        orWhereNotNull('is_not_null')->
        orWhereNotIn('is_not_in', [0, 1, 2, 3])->
        orWhereNotBetween('is_not_between', [0, 1,])->
        order('time_create', 'desc')->
        order('id')->
        select();*/
        echo "\r\n";
        ORM::table('delete_table')->where('id', 1)->ignore()->delete();
        var_dump('this is response');
        exit();
        return;
    }

    public function uploadAct() {
        $post = Request::post();
        $file = Request::file();
    }
}
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
        ORM::table('user us')->
        leftJoin('user_group ug', 'us.id_group', 'ug.id')->
        where('us.id', 123)->
        where(function ($query) {
//            var_dump($query);
            $query->where('us.status', 1)
                  ->where(function ($query) {
                      $query->where('ug.status', '1');
                  })
                  ->where('us.name', 'hentai');;
        })->
        orWhere([['ug.admin', false], ['ug.name', null],])->
        where('us.time_create', '>', '1919-08-10 11:45:14')->
        orWhereRaw('us.id in (?,?)', [1, 2])->
        orWhereRaw('us.id in (2,1)')->
        orWhereNotNull('us.id')->
        orWhereNotIn('us.id', [0, 1, 2, 3])->
        orWhereNotBetween('us.id', [0, 1,])->
        order('us.time_create', 'desc')->
        order('us.id')->
        select();
        echo "\r\n";
//        ORM::table('delete_table')->where('id', 1)->ignore()->delete();
//        var_dump('this is response');
//        ORM::table('update_table')->where('id', 1)->ignore()->update(['upd_col' => 1]);
//        ORM::table('insert_table')->insert(['hentai' => 'at home']);
//        ORM::table('insert_select_table')->insertSelect();
//        var_dump('this is response');
        exit();
        return;
    }

    public function uploadAct() {
        $post = Request::post();
        $file = Request::file();
    }
}
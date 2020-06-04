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

class Debug extends Kernel {

    public function emptyAct() {
        var_dump('this is a action');
    }

    public function dbAct() {
        var_dump('this is a db query check');
        DB::$logging=true;
        $data = DB::query('select * from dev.sougou_dict_cat where cate1=:cate', ['cate' => '人文科学']);
        var_dump(sizeof($data));
        $data = DB::query('select * from dev.sougou_dict_cat where cate1 in (:v)', [], ['人文科学', '电子游戏']);
        var_dump(sizeof($data));
        $data = DB::query('select * from dev.sougou_dict_cat where creator=:creator and cate1 in (:v)', ['creator'=>'爱爱爱'], ['人文科学', '电子游戏']);
        var_dump(sizeof($data));
        var_dump($data);
        $data = DB::query('select * from dev.sougou_dict_cat where cate1 in (:v) and creator in (:v);', [], ['人文科学', '自然科学'], ['搜狗拼音输入法', '爱爱爱']);
        var_dump(sizeof($data));
        $data=DB::query('insert into dev.test3 (v1, v2) values (:v)',[],['v1'=>'a','v2'=>'b']);
        var_dump($data);
        $data=DB::query('insert into dev.test3 (v1, v2) values (:v)',[],[['v1'=>'c','v2'=>'d'],['v1'=>'e','v2'=>'f']]);
        var_dump($data);
        $data=DB::query('insert into dev.test3 (:k) values (:v)',[],[['v1'=>'g','v2'=>'h'],['v1'=>'i','v2'=>'j']]);
        var_dump($data);
        var_dump(DB::getErr());
        var_dump(DB::getErrCode());
    }
}
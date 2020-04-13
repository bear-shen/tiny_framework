<?php namespace Controller;

use Lib\Request;
use Lib\Response;

class Debug extends Kernel {
    public function emptyAct() {
//        var_dump(func_get_args());
//        var_dump('executed');
//        Response::setHeader('Accept: javascript/json');
//        Response::setCookie(['name'=>'zzz','value'=>'z11']);
        return 'this is response'."\r\n";
    }

    public function uploadAct(){
        $post=Request::post();
        $file=Request::file();
    }
}
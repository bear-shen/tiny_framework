<?php namespace Controller;

use Lib\Response;

class Spd extends Kernel {
    public function emptyAct() {
//        var_dump(func_get_args());
//        var_dump('executed');
//        Response::setHeader('Accept: javascript/json');
//        Response::setCookie(['name'=>'zzz','value'=>'z11']);
        return 'this is response'."\r\n";
    }
}
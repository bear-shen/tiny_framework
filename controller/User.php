<?php namespace Controller;

use Lib\Request;

class User extends Kernel {
    function loginAct() {}
    function registerAct(Request $request) {
        $data=$request->data;
    }
    function captchaAct() {}
}
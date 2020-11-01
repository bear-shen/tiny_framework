<?php namespace Controller;

use Lib\Request;
use Lib\Response;

class User extends Kernel {
    function loginAct() {
        $data=Request::data();
        return $data;
    }
    function registerAct(Request $request) {
        $data=Request::data();
        return $data;
    }
    function captchaAct() {}
}
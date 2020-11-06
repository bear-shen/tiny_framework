<?php namespace Controller;

use Lib\Captcha;
use Lib\Request;
use Lib\Response;

class User extends Kernel {
    function loginAct() {
        $data = Request::data() + [
                'catpcha' => '',
                'name'    => '',
                'pass'    => '',
            ];
        $user = \Model\User::findUser($data['name']);

        if (empty($user))
            return $this->apiErr(1001, 'user not found');
        if (\Model\User::passMake($data['pass']) != $user['password'])
            return $this->apiErr(1002, 'invalid password');

        return $data;
    }

    function registerAct(Request $request) {
        $data = Request::data();
        return $data;
    }

    function captchaAct() {
        $captcha=new Captcha();
        Response::setHeader('content-type: image/png');
        return $captcha->getImg();
    }
}
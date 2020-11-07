<?php namespace Controller;

use Lib\Captcha;
use Lib\GenFunc;
use Lib\Request;
use Lib\Response;
use Lib\Session;

class User extends Kernel {
    function loginAct() {
        $data    = Request::data() + [
                'catpcha' => '',
                'name'    => '',
                'pass'    => '',
            ];
        $captcha = Session::get('captcha');
        if ($captcha != $data['captcha'])
            return $this->apiErr(1000, 'invalid captcha');
        Session::del('captcha');
        $user = \Model\User::findUser($data['name']);

        if (empty($user))
            return $this->apiErr(1001, 'user not found');
        if (\Model\User::passMake($data['pass']) != $user['password'])
            return $this->apiErr(1002, 'invalid password');
        Session::set('uid', $user['id']);
        return $this->apiRet();
    }

    function registerAct() {
        $data    = Request::data() + [
                'catpcha' => '',
                'name'    => '',
                'mail'    => '',
                'pass'    => '',
            ];
        $captcha = Session::get('captcha');
        Session::del('captcha');

        if ($captcha != $data['captcha'])
            return $this->apiErr(1000, 'invalid captcha');
        if (empty($data['name']))
            return $this->apiErr(1001, 'empty name');
        if (empty($data['mail']))
            return $this->apiErr(1001, 'empty mail');
        if (empty($data['pass']))
            return $this->apiErr(1001, 'empty pass');

        $uid = \Model\User::createUser(GenFunc::array_only($data, ['name', 'mail', 'pass']));

        if (!is_int($uid))
            return $this->apiErr(1002, $uid);

        Session::set('uid', $uid);
        return $this->apiRet();
    }

    function captchaAct() {
        $captcha = new Captcha();
        $data    = $captcha->getImg();
        Session::set('captcha', $captcha->getCode());
        Response::setHeader('content-type: image/png');
        return $data;
    }
}
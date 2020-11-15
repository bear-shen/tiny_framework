<?php namespace Controller;

use Lib\Captcha;
use Lib\GenFunc;
use Lib\Request;
use Lib\Response;
use Lib\Session;
use Model\User as UserModel;
use Model\UserGroup;

class User extends Kernel {
    function loginAct() {
        $data    = Request::data() + [
                'catpcha' => '',
                'name'    => '',
                'pass'    => '',
            ];
        $captcha = Session::get('captcha');
        if (strtolower($captcha) != strtolower($data['captcha']))
            return $this->apiErr(1000, 'invalid captcha');
        Session::del('captcha');

        $user = UserModel::findUser($data['name']);

        if (empty($user))
            return $this->apiErr(1001, 'user not found');
        if (UserModel::passMake($data['pass']) != $user['password'])
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
        if (strtolower($captcha) != strtolower($data['captcha']))
            return $this->apiErr(1010, 'invalid captcha' . $captcha);
//        Session::del('captcha');

        if (empty($data['name']))
            return $this->apiErr(1011, 'empty name');
        if (empty($data['mail']))
            return $this->apiErr(1011, 'empty mail');
        if (empty($data['pass']))
            return $this->apiErr(1011, 'empty pass');

        $data['pass'] = UserModel::passMake($data['pass']);
        $uid          = UserModel::createUser(GenFunc::array_only($data, ['name', 'mail', 'pass']));

        if (!is_int($uid))
            return $this->apiErr(1012, $uid);

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

    function listAct() {
        $data = Request::data() + [
                'page'  => '',
                'name'  => '',
                'group' => '',
            ];
        //
        $curUid  = Session::get('uid');
        $isAdmin = UserModel::isAdmin($curUid);
        if (!$isAdmin) return $this->apiErr(1020, 'not a admin');
        $userList = UserModel::listUser($data['page'], $data['name'], $data['group']);
        $result   = [];
        foreach ($userList as $user) {
            $result[] = [
                'id'          => $user['id'],
                'name'        => $user['name'],
                'mail'        => $user['mail'],
                'description' => '',
                'group'       => [
                    'id'          => $user['group_id'],
                    'name'        => $user['group_name'],
                    'description' => $user['group_description'],
                    'admin'       => $user['group_admin'],
                ],
                'status'      => $user['status'],
                'time_create' => $user['time_create'],
                'time_update' => $user['time_update'],
            ];
        }
        return $this->apiRet(['data' => $result, 'query' => $data]);
    }

    function modAct() {
    }
}
<?php namespace Controller;

use Lib\Captcha;
use Lib\DB;
use Lib\GenFunc;
use Lib\ORM;
use Lib\Request;
use Lib\Response;
use Lib\Session;
use Model\User as UserModel;
use Model\UserGroup;

class User extends Kernel {
    function loginAct() {
        $data    = $this->validate(
            [
                'catpcha' => 'required|string',
                'name'    => 'required|string',
                'pass'    => 'required|string',
            ]);
        $captcha = Session::get('captcha');
        if (strtolower($captcha) != strtolower($data['captcha']))
            return $this->apiErr(1000, 'invalid captcha');
        Session::del('captcha');

        $user = ORM::table('user')->
        where('name', $data['name'])->
        orWhere('mail', $data['name'])->first();

        if (empty($user))
            return $this->apiErr(1001, 'user not found');
        if ($this->makePass($data['pass']) != $user['password'])
            return $this->apiErr(1002, 'invalid password');
        Session::set('uid', $user['id']);
        return $this->apiRet();
    }

    function registerAct() {
        $data    = $this->validate(
            [
                'catpcha' => 'required|string',
                'name'    => 'required|string',
                'mail'    => 'required|string',
                'pass'    => 'required|string',
            ]);
        $captcha = Session::get('captcha');
        if (strtolower($captcha) != strtolower($data['captcha']))
            return $this->apiErr(1010, 'invalid captcha' . $captcha);
        //Session::del('captcha');
        $ifDup = ORM::table('user')->where('mail', $data['mail'])->first();
        if ($ifDup) return $this->apiErr(1011, 'mail duplicated');
        $ifDup = ORM::table('user')->where('name', $data['name'])->first();
        if ($ifDup) return $this->apiErr(1011, 'name duplicated');
        //
        $data['pass'] = $this->makePass($data['pass']);
        ORM::table('user')->insert(
            GenFunc::array_only($data, ['name', 'mail', 'pass']) + [
                'id_group' => 2,
                'status'   => 1,
            ]
        );
        $uid = DB::lastInsertId();
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
        $curUid   = Session::get('uid');
        $curUser  = ORM::table('user')->where('id', $curUid)->first();
        $curGroup = ORM::table('user_group')->where('id', $curUser['id_group'])->first();
        //

        $isAdmin = $curGroup['admin'];
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

    private function makePass($password) {
        $password = md5(md5($password));
        return $password;
    }
}
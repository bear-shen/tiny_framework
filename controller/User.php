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
        Session::set('id_group', $user['id_group']);
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
                'id_group' => 2,//默认游客
                'status'   => 1,
            ]
        );
        $uid = DB::lastInsertId();
        if (!is_int($uid))
            return $this->apiErr(1012, $uid);
        Session::set('uid', $uid);
        Session::set('id_group', 2);
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
        $data = $this->validate(
            [
                'page'  => 'default:1|integer',
                'name'  => 'nullable|string',
                'group' => 'nullable|string',
            ]);
        //
        $curUid   = Session::get('uid');
        $curUser  = ORM::table('user')->where('id', $curUid)->first();
        $curGroup = ORM::table('user_group')->where('id', $curUser['id_group'])->first();
        $isAdmin  = $curGroup['admin'];
        if (!$isAdmin) return $this->apiErr(1020, 'not a admin');
        //
        $userList = ORM::table('user as us')->leftJoin('user_group gr', 'us.id_group', 'gr.id')->
        where(function ($orm) use ($data) {
            /** @var $orm ORM */
            if ($data['name']) {
                $orm->where('us.name', 'like', "%{$data['name']}%");
            }
            if ($data['group']) {
                $groupIdList = ORM::table('user_group')->where('name', 'like', "%{$data['group']}%")->select(['id']);
                $orm->whereIn('gr.id', $groupIdList);
            }
        })->page($data['page'] ?: 1)->
        select(
            [
                'us.id',
                'us.name',
                'us.mail',
                'us.description',
                'us.status',
                'us.time_create',
                'us.time_update',
                'gr.id           as group_id',
                'gr.name         as group_name',
                'gr.description  as group_description',
                'gr.admin        as group_admin'
            ]
        );
        $result   = [];
        foreach ($userList as $user) {
            $result[] = [
                'id'          => $user['id'],
                'name'        => $user['name'],
                'mail'        => $user['mail'],
                'description' => $user['description'],
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
        $data = $this->validate(
            [
                'id'          => 'required|string',
                'name'        => 'required|string',
                'group_id'    => 'required|string',
                'mail'        => 'required|string',
                'description' => 'required|string',
                'status'      => 'required|string',
            ]);
        //
        $user = ORM::table('user')->where('id', $data['id'])->first();
        if (!$user) return $this->apiErr(1030, 'user not found');
        $user['id_group']    = $data['group_id'];
        $user['name']        = $data['name'];
        $user['mail']        = $data['mail'];
        $user['description'] = $data['description'];
        $user['status']      = $data['status'];
        return $this->apiRet(['data' => $user,]);
    }

    private function makePass($password) {
        $password = md5(md5($password));
        return $password;
    }
}
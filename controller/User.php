<?php namespace Controller;

use Lib\Captcha;
use Lib\DB;
use Lib\GenFunc;
use Lib\ORM;
use Lib\Request;
use Lib\Response;
use Lib\Session;
use Model\User as UserModel;
use Model\UserGroup as UserGroupModel;

class User extends Kernel {
    function loginAct() {
        $data = $this->validate(
            [
                'captcha' => 'required|string',
                'name'    => 'required|string',
                'pass'    => 'required|string',
            ]);
        /*$captcha = Session::get('captcha');
        if (strtolower($captcha) != strtolower($data['captcha']))
            return $this->apiErr(1000, 'invalid captcha');
        Session::del('captcha');*/

//        ORM::$logging = true;
        $user = UserModel::where('name', $data['name'])->
        orWhere('mail', $data['name'])->first(
            [
                'id',
                'id_group',
                'name',
                'mail',
                'password',
                'status',
            ]
        );
//        var_dump(ORM::$log);
        if (empty($user))
            return $this->apiErr(1001, 'user not found');
        if ($this->makePass($data['pass']) != $user['password'])
            return $this->apiErr(1002, 'invalid password');
        Session::set('uid', $user['id']);
        Session::set('id_group', $user['id_group']);
        return $this->apiRet($user);
    }

    function registerAct() {
        $data    = $this->validate(
            [
                'captcha' => 'required|string',
                'name'    => 'required|string',
                'mail'    => 'required|string',
                'pass'    => 'required|string',
            ]);
        $captcha = Session::get('captcha');
        if (strtolower($captcha) != strtolower($data['captcha']))
            return $this->apiErr(1010, 'invalid captcha' . $captcha);
        //Session::del('captcha');
        $ifDup = UserModel::where('mail', $data['mail'])->first();
        if ($ifDup) return $this->apiErr(1011, 'mail duplicated');
        $ifDup = UserModel::where('name', $data['name'])->first();
        if ($ifDup) return $this->apiErr(1011, 'name duplicated');
        //
        $data['password'] = $this->makePass($data['pass']);
        $targetUserData   = GenFunc::array_only($data, ['name', 'mail', 'password']) + [
                'id_group' => 2,//默认游客
                'status'   => 1,
            ];
        UserModel::insert($targetUserData);
        $uid = DB::lastInsertId();
        Session::set('uid', $uid);
        Session::set('id_group', 2);
        $targetUserData['id'] = $uid;
        return $this->apiRet($targetUserData);
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
        $curUser  = UserModel::where('id', $curUid)->first();
        $curGroup = UserGroupModel::where('id', $curUser['id_group'])->first();
        $isAdmin  = $curGroup['admin'];
        if (!$isAdmin) return $this->apiErr(1020, 'not a admin');
        //
        $userGroupList  = UserGroupModel::select(
            [
                'id',
                'name',
                'description',
                'admin',
                'status',
                //                'auth',
            ]
        );
        $userGroupAssoc = GenFunc::value2key($userGroupList, 'id');

        $userList = UserModel::where(function ($query) use ($data, $userGroupList) {
            /** @var $query UserModel */
            if ($data['name']) {
                $query->where('name', 'like', "%{$data['name']}%");
            }
            if ($data['group']) {
                $targetGroupList = [];
                foreach ($userGroupList as $userGroup) {
                    /** @var $userGroup UserGroupModel */
                    if (stripos($userGroup->name, $data['group']) === false) continue;
                    $targetGroupList[] = $userGroup->id;
                }
                $query->whereIn('id_group', $targetGroupList);
            }
        })->page($data['page'] ?: 1)->
        select(
            [
                'id',
                'id_group',
                'name',
                'mail',
                'description',
                'status',
                'time_create',
                'time_update',
            ]
        );
        $result   = [];
        foreach ($userList as $user) {
            $result[] = [
                'id'          => $user['id'],
                'name'        => $user['name'],
                'mail'        => $user['mail'],
                'description' => $user['description'],
                'group'       => $userGroupAssoc[$user['id_group']],
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
//        ORM::$logging = true;
        $user = UserModel::where('id', $data['id'])->first();
        if (!$user) return $this->apiErr(1030, 'user not found');
        UserModel::where('id', $data['id'])->update(
            [
                'id_group'    => $data['group_id'],
                'name'        => $data['name'],
                'mail'        => $data['mail'],
                'description' => $data['description'],
                'status'      => $data['status'],
            ]
        );
//        var_dump(ORM::$log);
        return $this->apiRet(['data' => $user,]);
    }

    private function makePass($password) {
        $password = md5(md5($password));
        return $password;
    }
}
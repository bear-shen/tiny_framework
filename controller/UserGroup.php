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

class UserGroup extends Kernel {
    public function listAct() {
        $data      = $this->validate(
            [
                'page'  => 'default:1|integer',
                'name'  => 'nullable|string',
                'short' => 'default:0|integer',
            ]);
        $groupList = UserGroupModel::where(function ($orm) use ($data) {
            /** @var $orm UserGroupModel */
            if ($data['name']) {
                $orm->where('name', 'like', "%{$data['name']}%");
            }
        })->page($data['page'] ?: 1)->
        select(
            [
                'id',
                'name',
                'description',
                'admin',
                'status',
                'auth',
                'time_create',
                'time_update',
            ]
        );
        for ($i1 = 0; $i1 < sizeof($groupList); $i1++) {
            $groupList[$i1]                = $groupList[$i1]->toArray();
        }
        if ($data['short']) {
            $groupInfoList = [];
            foreach ($groupList as $group) {
                $groupInfoList[] = GenFunc::array_only($group, [
                    'id',
                    'name',
                    'description',
                    'admin',
                    'status',
                ]);
            }
            return $this->apiRet($groupInfoList);
        }
        for ($i1 = 0; $i1 < sizeof($groupList); $i1++) {
//            $groupList[$i1]                = $groupList[$i1]->toArray();
            $groupList[$i1]['control_dir'] = !empty($groupList[$i1]['auth']) ? json_decode($groupList[$i1]['auth'], true) ?? [] : [];
            $groupList[$i1]['user']        = [];
        }
        $groupList   = GenFunc::value2key($groupList, 'id');
        $groupIdList = array_column($groupList, 'id');
        $userList    = UserModel::whereIn('id_group', $groupIdList)->select(
            [
                'id',
                'id_group',
                'name',
                //'mail',
                'status',
                //'time_create',
                'time_update',
            ]
        );
        foreach ($userList as $user) {
            $groupList[$user['id_group']]['user'][] = $user;
        }

        return $this->apiRet(array_values($groupList));
    }

    public function modAct() {
        $data = $this->validate(
            [
                'id'          => 'default:0|integer',
                'name'        => 'required|string',
                'description' => 'default:|string',
                'admin'       => 'default:0|integer',
                'status'      => 'default:1|integer',
            ]);
        //
        $ifDupName = UserGroupModel::where('name', $data['name'])->first(['id']);
        if ($ifDupName && $data['id'] != $ifDupName['id']) return $this->apiErr(2002, 'group name duplicated');
        //
        if (!empty($data['id'])) {
            $curUserGroup = UserGroupModel::where('id', $data['id'])->first(['id']);
            if (empty($curUserGroup)) return $this->apiErr(2001, 'group not found');
            UserGroupModel::where('id', $data['id'])->update(
                [
                    'name'        => $data['name'],
                    'description' => $data['description'],
                    'admin'       => $data['admin'],
                    'status'      => $data['status'],
                ]
            );
            return $this->apiRet($data['id']);
        }
        UserGroupModel::insert(
            [
                'name'        => $data['name'],
                'description' => $data['description'],
                'admin'       => $data['admin'],
                'status'      => $data['status'],
            ]
        );
        return $this->apiRet(ORM::lastInsertId());
    }

    public function delAct() {
        return $this->apiErr(2010, 'not supported');
    }

    public function authAct() {
        $data = $this->validate(
            [
                'id'   => 'required|integer',
                'list' => 'required|array',
            ]);
        //
        if (empty($data['list'])) return $this->apiErr(2022, 'no auth');
        //
        $curUserGroup = UserGroupModel::where('id', $data['id'])->first();
        if (empty($curUserGroup)) return $this->apiErr(2021, 'group not found');
        //清空后重新写入
        $targetAuthList = [];
//        var_dump($data['list']);
        foreach ($data['list'] as $auth) {
            $nodeId           = empty($auth['id_node']) ? 0 : $auth['id_node'];
            $authItem         = GenFunc::array_only(
                $auth + [
                    'id_node' => $nodeId,
                    'access'  => 0,
                    'modify'  => 0,
                    'delete'  => 0,
                ], ['id_node', 'access', 'modify', 'delete']
            );
            $targetAuthList[] = $authItem;
        }
        UserGroupModel::where('id', $data['id'])->update(
            [
                'auth' => json_encode($targetAuthList, JSON_UNESCAPED_UNICODE)
            ]
        );
        return $this->apiRet($targetAuthList);
    }
}
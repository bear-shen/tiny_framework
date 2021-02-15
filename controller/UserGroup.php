<?php namespace Controller;

use Lib\Captcha;
use Lib\DB;
use Lib\GenFunc;
use Lib\ORM;
use Lib\Request;
use Lib\Response;
use Lib\Session;
use Model\Node;

class UserGroup extends Kernel {
    public function listAct() {
        $data      = $this->validate(
            [
                'page'  => 'default:1|integer',
                'name'  => 'nullable|string',
                'short' => 'default:0|integer',
            ]);
        $groupList = ORM::table('user_group')->
        where(function ($orm) use ($data) {
            /** @var $orm ORM */
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
                'time_create',
                'time_update',
            ]
        );
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
        $groupIdList      = array_column($groupList, 'id');
        $renamedGroupList = [];
        foreach ($groupList as $group) {
            $renamedGroupList[$group['id']] = $group + [
                    'control_dir' => [],
                    'user'        => [],
                ];
        }
        $authList = ORM::table('user_group_auth')->
        whereIn('id_group', $groupIdList)->
        select(
            [
                'id',
                'id_group',
                'id_node',
                '`access`',
                '`modify`',
                '`delete`',
            ]
        );
        foreach ($authList as $auth) {
            $authPath = Node::crumb($auth['id_group']);
            $authItem = $auth + [
                    'path' => empty($authPath) ? 'unknown' : implode('/', $authPath[0]['path']),
                ];
            $renamedGroupList[$authItem['id_group']]['control_dir'][]
                      = $authItem;
        }
        $userList = ORM::table('user')->whereIn('id_group', $groupIdList)->select(
            [
                'id',
                'id_group',
                'name',
                //'description',
                //'mail',
                //'password',
                'status',
                //'time_create',
                'time_update',
            ]
        );
        foreach ($userList as $user) {
            $renamedGroupList[$user['id_group']]['user'][] = $user;
        }
        return $this->apiRet(array_values($renamedGroupList));
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
        $ifDupName = ORM::table('user_group')->where('name', $data['name'])->first();
        if ($ifDupName) return $this->apiErr(2002, 'group name duplicated');
        //
        if (!empty($data['id'])) {
            $curUserGroup = ORM::table('user_group')->where('id', $data['id'])->first();
            if (empty($curUserGroup)) return $this->apiErr(2001, 'group not found');
            ORM::table('user_group')->where('id', $data['id'])->update(
                [
                    'name'        => $data['name'],
                    'description' => $data['description'],
                    'admin'       => $data['admin'],
                    'status'      => $data['status'],
                ]
            );
            return $this->apiRet($data['id']);
        }
        ORM::table('user_group')->insert(
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
        return $this->apiErr(2010, 'not supported');
    }
}
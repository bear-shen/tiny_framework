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
        $data             = $this->validate(
            [
                'page'  => 'default:1|integer',
                'name'  => 'nullable|string',
                'short' => 'default:0|integer',
            ]);
        $groupList        = ORM::table('user_group')->
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

    public function addAct() {
    }

    public function modAct() {
    }

    public function authAct() {
    }
}
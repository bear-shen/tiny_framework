<?php namespace Controller;

use Lib\Captcha;
use Lib\DB;
use Lib\GenFunc;
use Lib\ORM;
use Lib\Request;
use Lib\Response;
use Lib\Session;

class UserGroup extends Kernel {
    public function listAct() {
        $data        = $this->validate(
            [
                'page'  => 'default:1|integer',
                'name'  => 'nullable|string',
                'short' => 'default:0|integer',
            ]);
        $groupList   = ORM::table('user_group')->
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
        $groupIdList = array_column($groupList, 'id');
        $authList    = ORM::table('user_group_auth')->whereIn('id_group', $groupIdList)->select(
            [
                'id',
                'id_group',
                'id_node',
                'access',
                'modify',
                'delete',
            ]
        );
    }

    public function addAct() {
    }

    public function modAct() {
    }

    public function authAct() {
    }
}
<?php namespace Controller;

use Lib\ORM;

class TagGroup extends Kernel {

    function listAct() {
        $data      = $this->validate(
            [
                'name'  => 'nullable|string',
                //'page'  => 'nullable|integer',
                //'group' => 'nullable|integer',
                'node'  => 'nullable|integer',
                'short' => 'default:0|integer',
            ]);
        $groupList = ORM::table('tag_group')->
        where(function ($query) {
            /** @var $query ORM */
            if (!empty($data['name'])) {
                $query->where('name', 'like', '%' . $data['name'] . '%');
            }
            if (!empty($data['node'])) {
                $query->where('id_node', $data['node']);
            }
        })->
        select(
            $data['short'] ? [
                'id',
                'name',
                'alt',
                'description',
                'sort',
                'time_create',
                'time_update',
                'id_node',
                'status',
            ] : [
                'id',
                'name',
                'alt',
                'description',
            ]
        );
        if ($data['short']) {
            return $this->apiRet($groupList);
        }
        $groupIdList    = array_column($groupList, 'id');
        $groupListAssoc = [];
        foreach ($groupList as $group) {
            $groupListAssoc[$group['id']] = $group + ['child' => []];
        }
        $tagList = ORM::table('tag')->
        whereIn('id_group', $groupIdList)->
        select(
            [
                'id',
                'id_group',
                'name',
                'alt',
                'description',
                'time_update',
                'time_create',
                'status',
            ]
        );
        foreach ($tagList as $tag) {
            $groupListAssoc[$tag['id_group']]['child'][] = $tag;
        }
        return $this->apiRet(array_values($groupListAssoc));
    }

    function modAct() {
        $data = $this->validate(
            [
                'id'          => 'nullable|integer',
                //
                'name'        => 'required|string',
                'alt'         => 'required|string',
                'description' => 'required|string',
                'sort'        => 'default:0|integer',
                //'time_create' => 'required|string',
                //'time_update' => 'required|string',
                'node_id'     => 'default:0|integer',
                'status'      => 'default:1|integer',
            ]);

        //
        $ifDupName = ORM::table('tag_group')->where('name', $data['name'])->first(['id']);
        if ($ifDupName && $data['id'] != $ifDupName['id']) return $this->apiErr(3002, 'group name duplicated');
        //
        if (!empty($data['id'])) {
            $curTagGroup = ORM::table('tag_group')->where('id', $data['id'])->first(['id']);
            if (empty($curTagGroup)) return $this->apiErr(3001, 'group not found');
            ORM::table('tag_group')->where('id', $data['id'])->update(
                [
                    'sort'    => $data['sort'],
                    'id_node' => 0,
                    //'id_node' => $data['node_id'],
                    'status'  => $data['status'],
                    'name'        => $data['name'],
                    'alt'         => $data['alt'],
                    'description' => $data['description'],
                ]
            );
            return $this->apiRet($data['id']);
        }
        ORM::table('tag_group')->insert(
            [
                'sort'    => $data['sort'],
                'id_node' => 0,
                //'id_node' => $data['node_id'],
                'status'  => $data['status'],
                'name'        => $data['name'],
                'alt'         => $data['alt'],
                'description' => $data['description'],
            ]
        );
        $groupId = ORM::lastInsertId();
        return $this->apiRet($groupId);
    }

    function delAct() {
        return $this->apiErr(3101, 'not available');
        $data = $this->validate(
            [
                'id' => 'required|integer',
            ]);
        ORM::table('tag_group')->where('id', $data['id'])->update(
            [
                'status' => 0,
            ]
        );
        return $this->apiRet();
    }
}
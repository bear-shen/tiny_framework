<?php namespace Controller;

use Lib\ORM;
use Model\Tag as TagModel;
use Model\TagGroup as TagGroupModel;

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
        $groupList = TagGroupModel::where(function ($query) {
            /** @var $query ORM */
            if (!empty($data['name'])) {
                $query->where('name', 'like', '%' . $data['name'] . '%');
            }
            /*if (!empty($data['node'])) {
                $query->where('id_node', $data['node']);
            }*/
        })->select(
            $data['short'] ? [
                'id',
                'name',
                'alt',
                'description',
            ] : [
                'id',
                'name',
                'alt',
                'description',
                'sort',
                'time_create',
                'time_update',
                //                'id_node',
                'status',
            ]
        );
        if ($data['short']) {
            return $this->apiRet($groupList);
        }
        $groupIdList    = array_column($groupList, 'id');
        $groupListAssoc = [];
//        var_dump($groupList);
        foreach ($groupList as $group) {
            $groupListAssoc[$group['id']] = $group->toArray() + ['child' => []];
        }
        $tagList = [];
        if (!empty($groupIdList)) {
            $tagList = TagModel::whereIn('id_group', $groupIdList)->
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
        }
        foreach ($tagList as $tag) {
            $groupListAssoc[$tag['id_group']]['child'][] = $tag;
        }
        return $this->apiRet(array_values($groupListAssoc));
    }

    function modAct() {
        $data          = $this->validate(
            [
                'id'          => 'nullable|integer',
                //
                'name'        => 'required|string',
                'alt'         => 'required|string',
                'description' => 'required|string',
                'sort'        => 'default:0|integer',
                //'time_create' => 'required|string',
                //'time_update' => 'required|string',
                //                'node_id'     => 'default:0|integer',
                'status'      => 'default:1|integer',
            ]);
        $data['alt']   = explode(',', $data['alt']);
        $data['alt'][] = $data['name'];
        $data['alt']   = implode(',', array_keys(array_flip(array_filter($data['alt']))));
        //
        $ifDupName = TagGroupModel::where('name', $data['name'])->first(['id']);
        if ($ifDupName && $data['id'] != $ifDupName['id']) return $this->apiErr(3002, 'group name duplicated');
        //
        if (!empty($data['id'])) {
            $curTagGroup = TagGroupModel::where('id', $data['id'])->first(['id']);
            if (empty($curTagGroup)) return $this->apiErr(3001, 'group not found');
            TagGroupModel::where('id', $data['id'])->update(
                [
                    'sort'        => $data['sort'],
                    //                    'id_node'     => 0,
                    //'id_node' => $data['node_id'],
                    'status'      => $data['status'],
                    'name'        => $data['name'],
                    'alt'         => $data['alt'],
                    'description' => $data['description'],
                ]
            );
            return $this->apiRet($data['id']);
        }
        TagGroupModel::insert(
            [
                'sort'        => $data['sort'],
                //                'id_node'     => 0,
                //'id_node' => $data['node_id'],
                'status'      => $data['status'],
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
        TagGroupModel::where('id', $data['id'])->update(
            [
                'status' => 0,
            ]
        );
        return $this->apiRet();
    }
}
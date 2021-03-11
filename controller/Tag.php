<?php namespace Controller;

use Lib\ORM;

class Tag extends Kernel {
    function listAct() {
        $data      = $this->validate(
            [
                'name'  => 'nullable|string',
                'page'  => 'nullable|integer',
                'group' => 'nullable|integer',
                'short' => 'default:0|integer',
            ]);
        $groupList = ORM::table('tag')->
        where(function ($query) {
            /** @var $query ORM */
            if (!empty($data['name'])) {
                $query->where('name', 'like', '%' . $data['name'] . '%');
            }
            if (!empty($data['group'])) {
                $query->where('id_group', $data['group']);
            }
        })->
        select(
            $data['short'] ? [
                'id',
                'name',
                'alt',
                'description',
                'time_create',
                'time_update',
                'id_group',
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
                'id_group'    => 'required|string',
                'name'        => 'required|string',
                'alt'         => 'required|string',
                'description' => 'required|string',
                //'time_create' => 'required|string',
                //'time_update' => 'required|string',
                'status'      => 'default:1|integer',
            ]);
        //
        $ifDupName = ORM::table('tag')->
        where('name', $data['name'])->where('id_group', $data['id_group'])->
        first(['id']);
        if ($ifDupName && $data['id'] != $ifDupName['id']) {
            return $this->apiErr(4002, 'tag name duplicated');
        }
        //
        if (!empty($data['id'])) {
            $curTag = ORM::table('tag')->where('id', $data['id'])->first(['id']);
            if (empty($curTag)) return $this->apiErr(4001, 'tag not found');
            ORM::table('tag')->where('id', $data['id'])->update(
                [
                    'id_group' => $data['id_group'],
                    'status'   => $data['status'],
                    'name'        => $data['name'],
                    'alt'         => $data['alt'],
                    'description' => $data['description'],
                ]
            );
            return $this->apiRet($data['id']);
        }
        ORM::table('tag')->insert(
            [
                'id_group' => $data['id_group'],
                'status'   => $data['status'],
                'name'        => $data['name'],
                'alt'         => $data['alt'],
                'description' => $data['description'],
            ]
        );
        $tagId = ORM::lastInsertId();
        return $this->apiRet($tagId);
    }

    function delAct() {
        return $this->apiErr(4101, 'not available');
        $data = $this->validate(
            [
                'id' => 'required|integer',
            ]);
        ORM::table('tag')->where('id', $data['id'])->update(
            [
                'status' => 0,
            ]
        );
        return $this->apiRet();
    }
}
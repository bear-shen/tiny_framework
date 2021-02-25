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
        $groupList = ORM::table('tag tg')->
        leftJoin('tag_info ti', 'tg.id', 'ti.id')->
        where(function ($query) {
            /** @var $query ORM */
            if (!empty($data['name'])) {
                $query->where('ti.name', 'like', '%' . $data['name'] . '%');
            }
            if (!empty($data['group'])) {
                $query->where('tg.id_group', $data['group']);
            }
        })->
        select(
            $data['short'] ? [
                'tg.id',
                'ti.name',
                'ti.alt',
                'ti.description',
                'tg.time_create',
                'tg.time_update',
                'tg.id_group',
                'tg.status',
            ] : [
                'tg.id',
                'ti.name',
                'ti.alt',
                'ti.description',
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
        $tagList = ORM::table('tag tg')->
        leftJoin('tag_info ti', 'tg.id', 'ti.id')->
        whereIn('tg.id_group', $groupIdList)->
        select(
            [
                'tg.id',
                'tg.id_group',
                'ti.name',
                'ti.alt',
                'ti.description',
                'tg.time_update',
                'tg.time_create',
                'tg.status',
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
        $ifDupName = ORM::table('tag tg')->leftJoin('tag_info ti', 'tg.id', 'ti.id')->
        where('ti.name', $data['name'])->where('tg.id_group', $data['id_group'])->
        first(['tg.id']);
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
                ]
            );
            ORM::table('tag_info')->where('id', $data['id'])->update(
                [
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
            ]
        );
        $tagId = ORM::lastInsertId();
        ORM::table('tag_info')->insert(
            [
                'id'          => $tagId,
                'name'        => $data['name'],
                'alt'         => $data['alt'],
                'description' => $data['description'],
            ]
        );
        return $this->apiRet($tagId);
    }

    function delAct() {
        return $this->apiErr(4101, 'tag not found');
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
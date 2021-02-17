<?php namespace Controller;

use Lib\ORM;

class TagGroup extends Kernel {

    function listAct() {
        $data           = $this->validate(
            [
                'name'  => 'nullable|string',
                //'page'  => 'nullable|integer',
                //'group' => 'nullable|integer',
                'node'  => 'nullable|integer',
                'short' => 'default:0|integer',
            ]);
        $groupList      = ORM::table('tag_group tg')->
        leftJoin('tag_group_info ti', 'tg.id', 'ti.id')->
        where(function ($query) {
            /** @var $query ORM */
            if (!empty($data['name'])) {
                $query->where('ti.name', 'like', '%' . $data['name'] . '%');
            }
            if (!empty($data['node'])) {
                $query->where('tg.id_node', $data['node']);
            }
        })->
        select(
            [
                'tg.id',
                'ti.name',
                'ti.alt',
                'ti.description',
                'tg.sort',
                'tg.time_create',
                'tg.time_update',
                'tg.id_node',
                'tg.status',
            ]
        );
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
                'tg.id_group as group_id',
                'ti.name',
                'ti.alt',
                'ti.description',
                'tg.time_update',
                'tg.time_create',
                'tg.status',
            ]
        );
        foreach ($tagList as $tag) {
            $groupListAssoc[$tag['group_id']]['child'][] = $tag;
        }
        return $this->apiRet(array_values($groupListAssoc));
    }

    function modAct() {

    }

    function delAct() {
    }
}
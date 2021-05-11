<?php namespace Controller;

use Lib\GenFunc;
use Lib\ORM;
use Model\Tag as TagModel;
use Model\TagGroup as TagGroupModel;

class Tag extends Kernel {
    function listAct() {
        $data = $this->validate(
            [
                'name'  => 'nullable|string',
                'page'  => 'nullable|integer',
                'group' => 'nullable|integer',
                'short' => 'default:0|integer',
            ]);
//        var_dump($data);
        $tagList = TagModel::where(function ($query) use ($data) {
            /** @var $query ORM */
            if (!empty($data['name'])) {
                $query->where('name', 'like', '%' . $data['name'] . '%');
            }
            if (!empty($data['group'])) {
                $query->where('id_group', $data['group']);
            }
        })->
        select(
            $data['short'] != '1' ? [
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
                'id_group',
            ]
        );
//        var_dump($tagList);
        if (empty($tagList)) {
            return $this->apiRet($tagList);
        }
//        var_dump($tagList);
        $tagGroupIdList = array_keys(array_flip(array_column($tagList, 'id_group')));
//        var_dump($tagGroupIdList);
//        var_dump($data);
        /*$assocTagList   = [];
        $tagGroupIdList = [];
        for ($i1 = 0; $i1 < sizeof($tagList); $i1++) {
            $assocTagList[$tagList[$i1]['id']] = $tagList[$i1]->toArray();
            $tagGroupIdList[]                  = $tagList[$i1]['id_group'];
        }*/
        $tagGroupList      = TagGroupModel::whereIn('id', $tagGroupIdList)->select();
        $assocTagGroupList = [];
        for ($i1 = 0; $i1 < sizeof($tagGroupList); $i1++) {
            $assocTagGroupList[$tagGroupList[$i1]['id']] = $tagGroupList[$i1]->toArray();
        }
        for ($i1 = 0; $i1 < sizeof($tagList); $i1++) {
            $tagList[$i1]['group'] = $assocTagGroupList[$tagList[$i1]['id_group']];
        }
        return $this->apiRet($tagList);
    }

    function modAct() {
        $data          = $this->validate(
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
        $data['alt']   = explode(',', $data['alt']);
        $data['alt'][] = $data['name'];
        $data['alt']   = implode(',', array_keys(array_flip(array_filter($data['alt']))));
        //
        $ifDupName = TagModel::where('name', $data['name'])->where('id_group', $data['id_group'])->
        first(['id']);
        if ($ifDupName && $data['id'] != $ifDupName['id']) {
            return $this->apiErr(4002, 'tag name duplicated');
        }
        //
        if (!empty($data['id'])) {
            $curTag = TagModel::where('id', $data['id'])->first(['id']);
            if (empty($curTag)) return $this->apiErr(4001, 'tag not found');
            TagModel::where('id', $data['id'])->update(
                [
                    'id_group'    => $data['id_group'],
                    'status'      => $data['status'],
                    'name'        => $data['name'],
                    'alt'         => $data['alt'],
                    'description' => $data['description'],
                ]
            );
            return $this->apiRet($data['id']);
        }
        TagModel::insert(
            [
                'id_group'    => $data['id_group'],
                'status'      => $data['status'],
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
        TagModel::where('id', $data['id'])->update(
            [
                'status' => 0,
            ]
        );
        return $this->apiRet();
    }
}
<?php namespace Controller;

use Lib\DB;
use Lib\GenFunc;
use Lib\ORM;
use Model\AssocNodeFile;
use Model\Node;
use Model\File as FileModel;
use Model\Tag as TagModel;
use Model\TagGroup as TagGroupModel;
use Model\zzzNode;

class File extends Kernel {
    /**
     * @todo tag_group 的 id_node 没用
     * @todo 索引重新考虑一下
     */
    public function listAct() {
        $data   = $this->validate(
            [
                //根据目录出列表
                'type'   => 'default:list|string',//list favourite recycle
                //
                'method' => 'default:directory|string',//directory tag keyword
                'target' => 'default:0|string',
                //通用的
                'sort'   => 'nullable|string',
                'page'   => 'default:1|integer',
            ]);
        $sort   = Node::availSort($data['sort']);
        $status = Node::availStatus($data['type']);
        //
//        ORM::$logging=true;
        $nodeList       = Node::where(function (Node $query) use ($data) {
            switch ($data['method']) {
                default:
                case 'directory':
                    $query->where('id_parent', $data['target']);
                    break;
                case 'tag':
                    $query->whereRaw("FIND_IN_SET(?,list_tag_id)", $data['target']);
                    break;
                case 'keyword':
                    $query->whereRaw("MATCH(index)against(? in boolean mode)", $data['target']);
                    break;
            }
        })->where('status', $status[0], $status[1])->
        page($data['page'])->order($sort[0], $sort[1])->
        select(
            [
                'id',
                'id_parent',
                'status',
                'sort',
                'is_file',
                'name',
                'description',
                'id_cover',
                'list_tag_id',
                'list_node',
                'time_create',
                'time_update',
            ]);
        $fileNodeIdList = [];
        foreach ($nodeList as $node) {
            if (intval($node['id_cover'])) {
                $fileNodeIdList[] = $node['id_cover'];
            }
            if ($node['is_file'] != '1') continue;
            $fileNodeIdList[] = $node['id'];
        }
        $fileAssocList   = AssocNodeFile::whereIn('id_node', $fileNodeIdList)->where('status', 1)->select(
            [
                'id_node',
                'id_file',
            ]);
        $fileAssocIdList = GenFunc::value2key($fileAssocList, 'id_node');
        $fileList        = FileModel::whereIn('id', array_values($fileAssocIdList))->select(
            [
                'id',
                'hash',
                'type',
                'suffix',
                'size',
            ]
        );
        $assocFileList   = GenFunc::value2key($fileList, 'id');
        //
        $tagIdList = [];
        for ($i1 = 0; $i1 < sizeof($nodeList); $i1++) {
            $nodeList[$i1]['list_tag_id'] = explode(',', $nodeList[$i1]['list_tag_id']);
            foreach ($nodeList[$i1]['list_tag_id'] as $tagId) {
                $tagIdList[$tagId] = 1;
            }
        }
        $tagIdList         = array_keys($tagIdList);
        $tagList           = TagModel::whereIn('id', $tagIdList)->where('status', 1)->select();
        $tagListAssoc      = GenFunc::value2key($tagList, 'id');
        $tagGroupIdList    = array_keys(array_flip(array_column($tagList, 'id_group')));
        $tagGroupList      = TagGroupModel::whereIn('id', $tagGroupIdList)->where('status', 1)->select();
        $tagGroupListAssoc = GenFunc::value2key($tagGroupList, 'id');
        //
        for ($i1 = 0; $i1 < sizeof($nodeList); $i1++) {
            /** @var  $nodeList TagModel[] */
            $extInfo = [
                'raw'    => '',
                'normal' => '',
                'cover'  => '',
                'tag'    => [],
            ];
            if (
                $nodeList[$i1]['is_file'] == '1'
                && isset(
                    $assocFileList[$nodeList[$i1]['id']]
                )) {
                /** @var $file FileModel */
                $file    = $assocFileList[$nodeList[$i1]['id']];
                $extInfo = [
                               'raw'    => FileModel::getPathFromHash($file->hash, $file->suffix, $file->type, 'raw'),
                               'normal' => FileModel::getPathFromHash($file->hash, $file->suffix, $file->type, 'normal'),
                               'cover'  => FileModel::getPathFromHash($file->hash, $file->suffix, $file->type, 'preview'),
                           ] + $extInfo;
            }
            $nodeTagGroupAssoc = [];
            foreach ($nodeList[$i1]['list_tag_id'] as $tagId) {
                /** @var $tag TagModel */
                $tag = $tagListAssoc[$tagId];
                if (empty($nodeTagGroupAssoc[$tag->id_group])) {
                    $nodeTagGroupAssoc[$tag->id_group]        = $tagGroupListAssoc[$tag->id_group];
                    $nodeTagGroupAssoc[$tag->id_group]['sub'] = [];
                }
                $nodeTagGroupAssoc[$tag->id_group]['sub'] [] = $tag;
            }
            $extInfo['tag'] = $nodeTagGroupAssoc;
            $nodeList[$i1]  += $extInfo;
        }
        $navi = [
            ['id' => 0, 'name' => 'root', 'type' => 'directory',]
        ];
        $dir  = [];
        if ($data['method'] == 'directory') {
            $curNode        = Node::where('id', $data['target'])->first(
                [
                    'id',
                    'id_parent',
                    'is_file',
                    'name',
                    'list_node',
                ]
            );
            $dir            = [
                'id'   => $curNode['id'],
                'name' => $curNode['name'],
                'type' => 'directory',
            ];
            $curNodeList    = explode(',', $curNode->list_node);
            $parentNodeList = Node::whereIn('id', $curNodeList)->select();
            foreach ($parentNodeList as $parent) {
                $navi[] = [
                    'id'   => $parent['id'],
                    'name' => $parent['name'],
                    'type' => 'directory',
                ];
            }
        }

        return $this->apiRet(
            [
                'list' => $nodeList,
                'navi' => $navi,
                'dir'  => $dir,
            ]);
    }

    /**
     * @todo 更新索引
     */
    public function modAct() {
        $data   = $this->validate(
            [
                'id'          => 'required|integer',
                'title'       => 'required|string',
                'description' => 'nullable|string',
            ]);
        $ifNode = ORM::table('node')->
        where('id', $data['id'])->first();
        if (!$ifNode) return $this->apiErr(5001, 'node not found');
        $nodeInfo = ORM::table('node_info')->
        where('id', $data['id'])->update(
            [
                'name'        => $data['title'],
                'description' => $data['description'],
            ]
        );
        return $this->apiRet($data['id']);
    }


    /**
     */
    public function coverAct() {
        $data   = $this->validate(
            [
                'id'            => 'required|integer',
                //id_cover目前存的是file的id
                'node_cover_id' => 'default:0|integer',
            ]);
        $ifNode = ORM::table('node')->
        where('id', $data['id'])->first();
        if (!$ifNode) return $this->apiErr(5101, 'node not found');
        if (empty($data['node_cover_id'])) {
            $nodeInfo = ORM::table('node_info')->
            where('id', $data['id'])->update(
                [
                    'id_cover' => 0,
                ]
            );
            return $this->apiRet($data['id']);
        }
        //
        $coverFile = ORM::table('file as fl')->
        leftJoin('assoc_node_file as anf')->
        where('anf.status', 1)->
        where('anf.id_node', $data['node_cover_id'])->
        first(['fl.id']);
        if (!$coverFile) return $this->apiErr(5102, 'cover file not found');
        $nodeInfo = ORM::table('node_info')->
        where('id', $data['id'])->update(
            [
                'id_cover' => $coverFile['id'],
            ]
        );
        return $this->apiRet($data['id']);
    }

    /**
     */
    public function moveAct() {
        $data   = $this->validate(
            [
                'id'        => 'required|integer',
                'target_id' => 'required|integer',
            ]);
        $ifNode = ORM::table('node')->
        whereIn('id', [$data['id'], $data['target_id']])->first();
        if (!$ifNode && sizeof($ifNode) < 2) return $this->apiErr(5301, 'node not found');

        $node = ORM::table('node')->
        where('id', $data['id'])->
        update(['id_parent' => $data['target_id']]);
        return $this->apiRet($data['id']);
    }

    /**
     */
    public function deleteAct() {
        $data   = $this->validate(
            [
                'id' => 'required|integer',
            ]);
        $ifNode = ORM::table('node')->
        where('id', $data['id'])->first();
        if (!$ifNode) return $this->apiErr(5401, 'node not found');
        $node = ORM::table('node')->
        where('id', $data['id'])->
        update(['status' => 0]);
        return $this->apiRet($data['id']);
    }

    /**
     */
    public function uploadAct() {
    }

    /**
     */
    public function favouriteAct() {
        $data   = $this->validate(
            [
                'id' => 'required|integer',
            ]);
        $ifNode = ORM::table('node')->
        where('id', $data['id'])->first();
        if (!$ifNode) return $this->apiErr(5201, 'node not found');
        $targetStatus = $ifNode['status'] == 1 ? 2 : 1;
        $node         = ORM::table('node')->
        where('id', $data['id'])->
        update(['status' => $targetStatus]);
        return $this->apiRet($data['id']);
    }

    /**
     */
    public function recoverAct() {
        $data   = $this->validate(
            [
                'id' => 'required|integer',
            ]);
        $ifNode = ORM::table('node')->
        where('id', $data['id'])->first();
        if (!$ifNode) return $this->apiErr(5501, 'node not found');
        $node = ORM::table('node')->
        where('id', $data['id'])->
        update(['status' => 1]);
        return $this->apiRet($data['id']);
    }

    /**
     */
    public function delete_foreverAct() {
    }

    /**
     */
    public function mkdirAct() {
    }

    /**
     */
    public function versionAct() {
    }

    /**
     */
    public function version_modAct() {
    }

}
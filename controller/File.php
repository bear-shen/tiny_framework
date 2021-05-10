<?php namespace Controller;

use Lib\DB;
use Lib\GenFunc;
use Lib\ORM;
use Lib\Request;
use Model\AssocNodeFile;
use Model\Node;
use Model\File as FileModel;
use Model\Tag as TagModel;
use Model\TagGroup as TagGroupModel;
use Model\zzzNode;

class File extends Kernel {
    /**
     * @todo tag_group 的 id_node 没用
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
        $assocFileList = [];
        if (!empty($fileNodeIdList)) {
            $fileAssocList   = [];
            $fileAssocIdList = [];
            $fileList        = [];
            //
            $fileAssocList   = AssocNodeFile::whereIn('id_node', $fileNodeIdList)->where('status', 1)->select(
                [
                    'id_node',
                    'id_file',
                ]);
            $fileAssocIdList = GenFunc::value2key($fileAssocList, 'id_file');
            $fileList        = FileModel::whereIn('id', array_keys($fileAssocIdList))->select(
                [
                    'id',
                    'hash',
                    'type',
                    'suffix',
                    'size',
                ]
            );
            foreach ($fileList as $file) {
                $fileAssocInfo                            = $fileAssocIdList[$file['id']];
                $assocFileList[$fileAssocInfo['id_node']] = $file;
            }
        }
        //
        $tagIdList = [];
        for ($i1 = 0; $i1 < sizeof($nodeList); $i1++) {
            $nodeList[$i1]['list_tag_id'] = explode(',', $nodeList[$i1]['list_tag_id']);
            foreach ($nodeList[$i1]['list_tag_id'] as $tagId) {
                $tagIdList[$tagId] = 1;
            }
        }
        $tagListAssoc      = [];
        $tagGroupListAssoc = [];
        //
        if (!empty($tagIdList)) {
            $tagIdList    = array_keys($tagIdList);
            $tagList      = TagModel::whereIn('id', $tagIdList)->where('status', 1)->select();
            $tagListAssoc = GenFunc::value2key($tagList, 'id');
            if (!empty($tagList)) {
                $tagGroupIdList    = array_keys(array_flip(array_column($tagList, 'id_group')));
                $tagGroupList      = TagGroupModel::whereIn('id', $tagGroupIdList)->where('status', 1)->select();
                $tagGroupListAssoc = GenFunc::value2key($tagGroupList, 'id');
            }
        }
        //
        for ($i1 = 0; $i1 < sizeof($nodeList); $i1++) {
            /** @var  $nodeList TagModel[] */
            $nodeList[$i1] = $nodeList[$i1]->toArray();
            $extInfo       = [
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
                if (empty($tagListAssoc[$tagId])) continue;
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
            $curNode = Node::where('id', $data['target'])->first(
                [
                    'id',
                    'id_parent',
                    'is_file',
                    'name',
                    'list_node',
                ]
            );
//            var_dump($curNode);
            $dir            = [
                'id'   => $curNode['id'] ?? 0,
                'name' => $curNode['name'] ?? 'root',
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
                'name'        => 'required|string',
                'description' => 'nullable|string',
            ]);
        $ifNode = Node::where('id', $data['id'])->first();
        if (!$ifNode) return $this->apiErr(5001, 'node not found');
        $nodeInfo = Node::where('id', $data['id'])->update(
            [
                'name'        => $data['name'],
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
        $ifNode = Node::where('id', $data['id'])->first(['id']);
        if (!$ifNode) return $this->apiErr(5101, 'node not found');
        if (empty($data['node_cover_id'])) {
            $nodeInfo = Node::where('id', $data['id'])->update(
                [
                    'id_cover' => 0,
                ]
            );
            return $this->apiRet($data['id']);
        }
        //
        $coverNode = Node::where('id', $data['node_cover_id'])->first(['id', 'is_file', 'id_cover']);
        if (!$coverNode) {
            return $this->apiErr(5102, 'cover file not found');
        }
        $coverFileId  = AssocNodeFile::where('id_node', [$coverNode->id, $coverNode->id_cover])->
        where('status', 1)->
        select();
        $targetFileId = '';
        foreach ($coverFileId as $coverFileAssoc) {
            /** @var AssocNodeFile $coverFileAssoc */
            if ($coverFileAssoc->id_node == $coverNode->id && empty($targetFileId)) {
                $targetFileId = $coverFileAssoc->id_file;
            } elseif ($coverFileAssoc->id_node == $coverNode->id_cover) {
                $targetFileId = $coverFileAssoc->id_file;
            }
        }
        Node::where('id', $data['id'])->update(
            [
                'id_cover' => $targetFileId,
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
        $ifNode = Node::whereIn('id', [$data['id'], $data['target_id']])->select(['id']);
        if (!$ifNode && sizeof($ifNode) < 2) return $this->apiErr(5301, 'node not found');
        $node = Node::where('id', $data['id'])->
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
        $ifNode = Node::where('id', $data['id'])->first(['id']);
        if (!$ifNode) return $this->apiErr(5401, 'node not found');
        Node::where('id', $data['id'])->
        update(['status' => 0]);
        return $this->apiRet($data['id']);
    }

    /**
     */
    public function favouriteAct() {
        $data   = $this->validate(
            [
                'id' => 'required|integer',
            ]);
        $ifNode = Node::where('id', $data['id'])->first(['id', 'status']);
        if (!$ifNode) return $this->apiErr(5201, 'node not found');
        $targetStatus = $ifNode['status'] == '1' ? 2 : 1;
        Node::where('id', $data['id'])->
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
        $ifNode = Node::where('id', $data['id'])->first();
        if (!$ifNode) return $this->apiErr(5501, 'node not found');
        $node = Node::where('id', $data['id'])->
        update(['status' => 1]);
        return $this->apiRet($data['id']);
    }

    /**
     */
    public function uploadAct() {
        var_dump(Request::data());
        $data    = $this->validate(
            [
                'dir' => 'required|integer',
            ]);
        $tmpFile = Request::file();
        if (empty($tmpFile['file'])) return $this->apiErr(5601, 'file not found');
        $tmpFile = $tmpFile['file'] + [
                'name'     => '',
                'tmp_name' => '',
            ];
        list($type, $suffix) = FileModel::getSuffixFromName($tmpFile);
        $hash           = FileModel::getHashFromFile($tmpFile['tmp_name']);
        $targetFilePath = FileModel::getPathFromHash($hash, $suffix, $type, 'raw', true);
        if (!file_exists($targetFilePath)) {
            $dir = dirname($targetFilePath);
            if (!file_exists($dir)) {
                mkdir($dir, 0755, true);
            }
            rename($tmpFile['tmp_name'], $targetFilePath);
        }



        var_dump($data);
        var_dump($reqFile);
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
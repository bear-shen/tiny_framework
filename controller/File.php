<?php namespace Controller;

use Job\Encoder;
use Job\Index;
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
                'type'      => 'default:list|string',//list favourite recycle
                //
                'method'    => 'default:folder|string',//folder tag keyword
                'target'    => 'default:0|string',
                //通用的
                'sort'      => 'nullable|string',
                'page'      => 'default:1|integer',
                'node_only' => 'default:0|integer',
                'dir_only'  => 'default:0|integer',
                'all_file'  => 'default:0|integer',
            ]);
        $sort   = Node::availSort($data['sort']);
        $status = Node::availStatus($data['type']);
        //
//        ORM::$logging=true;
        $nodeList = Node::where(function (Node $query) use ($data) {
            switch ($data['method']) {
                default:
                case 'folder':
                    $query->where('id_parent', $data['target']);
                    break;
                case 'tag':
                    $query->whereRaw("FIND_IN_SET(?,list_tag_id)", [$data['target']]);
                    break;
                case 'keyword':
                    $query->whereRaw("MATCH(`index`)against(? in boolean mode)", [$data['target']]);
                    break;
            }
            if ($data['dir_only']) {
                $query->where('is_file', 0);
            }
            if ($data['all_file']) {
                $query->where('is_file', 1);
            }
        })->where('status', $status[0], $status[1]);
        if (!$data['all_file']) {
            $nodeList = $nodeList->page($data['page']);
        }
        $nodeList = $nodeList->order($sort[0], $sort[1])->
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
        if ($data['node_only']) {
            return $this->apiRet($nodeList);
        }
        $fileNodeIdList = [];
        foreach ($nodeList as $node) {
            if ($node['is_file'] != '1') continue;
            $fileNodeIdList[] = $node['id'];
        }
        $fileAssocNodeId = [];
        $fileAssocFileId = [];
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
            $fileIdList      = array_keys($fileAssocIdList);
            foreach ($nodeList as $node) {
                if (intval($node['id_cover'])) {
                    $fileIdList[] = $node['id_cover'];
                }
            }
            $fileList = FileModel::whereIn('id', $fileIdList)->select(
                [
                    'id',
                    'hash',
                    'type',
                    'suffix',
                    'suffix_normal',
                    'suffix_preview',
                    'size',
                    'status',
                ]
            );
            foreach ($fileList as $file) {
                $fileAssocFileId[$file['id']] = $file;
                //
                $fileAssocInfo                              = $fileAssocIdList[$file['id']];
                $fileAssocNodeId[$fileAssocInfo['id_node']] = $file;
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
        $tagAssoc      = [];
        $tagGroupAssoc = [];
        //
        if (!empty($tagIdList)) {
            $tagIdList = array_keys($tagIdList);
            $tagList   = TagModel::whereIn('id', $tagIdList)->where('status', 1)->select();
            $tagAssoc  = GenFunc::value2key($tagList, 'id');
            if (!empty($tagList)) {
                $tagGroupIdList = array_keys(array_flip(array_column($tagList, 'id_group')));
                $tagGroupList   = TagGroupModel::whereIn('id', $tagGroupIdList)->where('status', 1)->select();
                foreach ($tagGroupList as $tagGroup) {
                    $tagGroupAssoc[$tagGroup['id']] = $tagGroup->toArray();
                }
            }
        }
        //
        for ($i1 = 0; $i1 < sizeof($nodeList); $i1++) {
            /** @var  $nodeList Node[] */
            $nodeList[$i1] = $nodeList[$i1]->toArray();
            $extInfo       = [
                'type'        => $nodeList[$i1]['is_file'] != '1' ? 'folder' : '',
                'file_status' => '',
                'raw'         => '',
                'normal'      => '',
                'cover'       => '',
                'tag'         => [],
            ];
            if (
                $nodeList[$i1]['is_file'] == '1'
                && isset(
                    $fileAssocNodeId[$nodeList[$i1]['id']]
                )) {
                /** @var $file FileModel */
                $file                   = $fileAssocNodeId[$nodeList[$i1]['id']];
                $extInfo['file_status'] = $file->status;
                $extInfo                =
                    [
                        'normal' => !$file->suffix_normal ? '' : FileModel::getPathFromHash($file->hash, $file->suffix_normal, $file->type, 'normal'),
                        'cover'  => !$file->suffix_preview ? '' : FileModel::getPathFromHash($file->hash, $file->suffix_preview, $file->type, 'preview'),
                        'raw'    => FileModel::getPathFromHash($file->hash, $file->suffix, $file->type, 'raw'),
                        'type'   => $file->type,
                    ] + $extInfo;
            }
            if ($nodeList[$i1]['id_cover'] != '0') {
                if (!empty($fileAssocFileId[$nodeList[$i1]['id_cover']])) {
                    $file              = $fileAssocFileId[$nodeList[$i1]['id_cover']];
                    $extInfo ['cover'] = !$file->suffix_preview ? '' : FileModel::getPathFromHash($file->hash, $file->suffix_preview, $file->type, 'preview');
                }
            }
            $nodeTagGroupAssoc = [];
            foreach ($nodeList[$i1]['list_tag_id'] as $tagId) {
                /** @var $tag TagModel */
                if (empty($tagAssoc[$tagId])) continue;
                $tag = $tagAssoc[$tagId];

                if (empty($nodeTagGroupAssoc[$tag->id_group])) {
                    $nodeTagGroupAssoc[$tag->id_group]        = $tagGroupAssoc[$tag->id_group];
                    $nodeTagGroupAssoc[$tag->id_group]['sub'] = [];
                }
                $nodeTagGroupAssoc[$tag->id_group]['sub'] [] = $tag;
            }
            $extInfo['tag'] = array_values($nodeTagGroupAssoc);
            $nodeList[$i1]  += $extInfo;
        }
        $navi = [
            ['id' => 0, 'name' => 'root', 'type' => 'folder',]
        ];
        $dir  = [];
        if ($data['method'] == 'folder') {
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
            $dir = [
                'id'   => $curNode['id'] ?? 0,
                'name' => $curNode['name'] ?? 'root',
                'type' => 'folder',
            ];
            if (!empty($curNode)) {
//                var_dump($data['target']);
//                var_dump($curNode);
                $curNodeList    = $curNode['list_node'] ? explode(',', $curNode['list_node']) : [];
                $parentNodeList = [];
                if (!empty($curNodeList)) {
                    $parentNodeList = Node::whereIn('id', $curNodeList)->select();
                }
                foreach ($parentNodeList as $parent) {
                    $navi[] = [
                        'id'   => $parent['id'],
                        'name' => $parent['name'],
                        'type' => 'folder',
                    ];
                }
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
     */
    public function uploadAct() {
        $data    = $this->validate(
            [
                'dir' => 'required|integer',
            ]);
        $tmpFile = Request::file();
        if (empty($tmpFile['file'])) return $this->apiErr(5201, 'file not found');
        $tmpFile  = $tmpFile['file'] + [
                'name'     => '',
                'tmp_name' => '',
            ];
        $fileSize = filesize($tmpFile['tmp_name']);
        list($type, $suffix) = FileModel::getTypeFromName($tmpFile['name']);
        $hash           = FileModel::getHashFromFile($tmpFile['tmp_name']);
        $targetFilePath = FileModel::getPathFromHash($hash, $suffix, $type, 'raw', true);
        //
        $duplicated = true;
        $fileId     = 0;
        //获得文件id
        $file = null;
        if (file_exists($targetFilePath)) {
            $file = FileModel::where('hash', $hash)->selectOne(['id']);
            if (!empty($file)) $fileId = $file['id'];
        } else {
            $duplicated = false;
            $dir        = dirname($targetFilePath);
            if (!file_exists($dir)) {
                mkdir($dir, 0664, true);
            }
            rename($tmpFile['tmp_name'], $targetFilePath);
        }
        if (empty($file)) {
            $needEncoder = false;
            switch ($type) {
                case 'image':
                case 'video':
                case 'audio':
                    $needEncoder = true;
                    break;
                default:
                    break;
            }
            FileModel::ignore()->insert(
                [
                    //'id'     => 0,
                    'hash'   => $hash,
                    'type'   => $type,
                    'suffix' => $suffix,
                    'status' => $needEncoder ? 2 : 1,
                    'size'   => $fileSize,
                ]
            );
            $fileId = DB::lastInsertId();
            if ($needEncoder) \Job\Kernel::push(Encoder::class, $fileId);
        }
        //检查node重名
        $targetNode = Node::where('name', $tmpFile['name'])->where('id_parent', $data['dir'])->selectOne(
            [
                'id',
                'id_parent',
                'status',
                'sort',
                'is_file',
                'name',
                //'description',
                //'id_cover',
                //'list_tag_id',
                'list_node',
                //'index',
            ]
        );
        //写入node
        if (empty($targetNode)) {
            $parent = Node::where('id', $data['dir'])->selectOne(['id', 'list_node', 'is_file']);
            if (empty($parent)) {
                $parent = [
                    'id'        => '0',
                    'list_node' => '',
                    'is_file'   => '0',
                ];
            }
            if ($parent['is_file'] == '1')
                return $this->apiErr(5203, 'parent is a file');
            $nodeList   = $parent['list_node'] ? explode(',', $parent['list_node']) : [];
            $nodeList[] = $parent['id'];
            $targetNode = [
                //'id'        => '',
                'id_parent' => $data['dir'],
                'status'    => '1',
                'sort'      => '0',
                'is_file'   => '1',
                'name'      => $tmpFile['name'],
                //'description' => '',
                'id_cover'  => '0',
                //'list_tag_id' => '',
                'list_node' => implode(',', $nodeList),
                //'index'       => '',
            ];
            Node::insert($targetNode);
            $targetNode['id'] = DB::lastInsertId();
        }
        if ($targetNode['is_file'] != '1')
            return $this->apiErr(5202, 'duplicated name directory');
        //处理文件关联
        $ifDup = AssocNodeFile::where('id_node', $targetNode['id'])->where('id_file', $fileId)->
        selectOne();
        if ($ifDup) {
            AssocNodeFile::where('id_node', $targetNode['id'])->
            where('id_file', $fileId)->
            update(['status' => 1]);
        } else {
            AssocNodeFile::insert(
                [
                    'id_node' => $targetNode['id'],
                    'id_file' => $fileId,
                    'status'  => 1,
                ]);
        }
        AssocNodeFile::where('id_node', $targetNode['id'])->
        where('id_file', '<>', $fileId)->
        update(['status' => 0]);
        return $this->apiRet($targetNode['id']);
    }

    /**
     */
    public function mkdirAct() {
        $data  = $this->validate(
            [
                'dir_id'      => 'default:0|integer',
                'name'        => 'default:|string',
                'description' => 'default:|string',
            ]);
        $ifDup = Node::where('id_parent', $data['dir_id'])->
        where('name', $data['name'])->selectOne();
        if ($ifDup) return $this->apiErr(5301, 'name duplicated');
        $parent = Node::where('id', $data['dir_id'])->selectOne(['id', 'list_node',]);
        if (empty($parent)) $parent = [
            'id'        => '0',
            'list_node' => '',
        ];
        $nodeList   = $parent['list_node'] ? explode(',', $parent['list_node']) : [];
        $nodeList[] = $parent['id'];
        $nodeData   = [
            //'id'          => '',
            'id_parent'   => $parent['id'],
            'status'      => '1',
            'sort'        => '0',
            'is_file'     => '0',
            'name'        => $data['name'],
            'description' => $data['description'],
            'id_cover'    => '0',
            'list_tag_id' => '',
            'list_node'   => implode(',', $nodeList),
            'index'       => '',
        ];
        Node::insert($nodeData);
        $dirId = DB::lastInsertId();
        \Job\Kernel::push(Index::class, $dirId);
        return $this->apiRet($dirId);
    }

    /**
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
        \Job\Kernel::push(Index::class, $data['id']);
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

    //---------------------------------------


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
        $data     = $this->validate(
            [
                'id'        => 'required|integer',
                'target_id' => 'required|integer',
            ]);
        $ifTarget = empty($data['target_id']) ? ['id' => 0, 'list_node' => '',] : Node::where('id', $data['target_id'])->selectOne(['id', 'list_node',]);
        if (!$ifTarget) return $this->apiErr(5301, 'target not found');
        $node = Node::where('id', $data['id'])->whereIn('status', [0, 1, 2])->selectOne(
            [
                'id',
                'id_parent',
                'list_node',
                'status',
            ]
        );
        if (!$node) return $this->apiErr(5302, 'node not found');
        //
        $preListNode  = $node['list_node'];
        $targetTree   = strlen($ifTarget['list_node']) ? explode(',', $ifTarget['list_node']) : [];
        $targetTree[] = $ifTarget['id'];
        $targetTree   = implode(',', $targetTree);
        Node::where('id', $data['id'])->
        update(
            [
                'id_parent' => $data['target_id'],
                'list_node' => $targetTree,
                'status'    => intval($node['status']) > 0 ? $node['status'] : 1,
            ]);
//        $subNode = Node::whereRaw("FIND_IN_SET(?,list_tag_id)", [$data['id']])->update(['id', 'list_node']);
        DB::execute(
            'update node set list_node=replace(list_node,?,?) where find_in_set(?,list_node)',
            [$preListNode, $targetTree, $data['id']]
        );
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
    public function delete_foreverAct() {
        $data = $this->validate(
            [
                'id' => 'required|integer',
            ]);
        //删除还是没有做成彻底干掉的形式，先改成-1吧
        Node::where('id', $data['id'])->update(['status' => -1]);
        Node::whereRaw("FIND_IN_SET(?,list_node)", [$data['id']])->update(['status' => -1]);
        return $this->apiRet($data['id']);
    }

    /**
     * @debug
     */
    public function versionAct() {
        $data     = $this->validate(
            [
                'node_id' => 'required|integer',
            ]);
        $nodeInfo = Node::where('id', $data['node_id'])->first();
        if (empty($nodeInfo)) return $this->apiErr(5601, 'node not found');
        $fileAssocList = AssocNodeFile::where('id_node', $nodeInfo['id'])->select(['id_file']);
        $fileIdArr     = array_column($fileAssocList, 'id_file');
        $currentFileId = 0;
        foreach ($fileAssocList as $fileAssoc) {
            if ($fileAssoc['status'] != '1') continue;
            $currentFileId = $fileAssoc['id_file'];
        }
        $fileList = FileModel::whereIn('id', $fileIdArr)->select();
        $result   = [];
        foreach ($fileList as $file) {
            $cur      = [
                'id'          => $file['id'],
                'raw'         => '',
                'normal'      => '',
                'cover'       => '',
                //
                //                'cover_id'    => '',
                //                'title'       => '',
                //                'description' => '',
                'size'        => $file['size'],
                'hash'        => $file['hash'],
                'type'        => $file['type'],
                //                'favourite'   => '',
                'time_create' => $file['time_create'],
                'time_update' => $file['time_update'],
                'is_current'  => $currentFileId == $file['id'] ? 1 : 0,
            ];
            $extInfo  =
                [
                    'raw'    => FileModel::getPathFromHash($file->hash, $file->suffix, $file->type, 'raw'),
                    'normal' => !$file->suffix_normal ? '' : FileModel::getPathFromHash($file->hash, $file->suffix_normal, $file->type, 'normal'),
                    'cover'  => !$file->suffix_preview ? '' : FileModel::getPathFromHash($file->hash, $file->suffix_preview, $file->type, 'preview'),
                ] + $extInfo;
            $result[] = $cur;
        }
        return $this->apiRet($result);
    }

    /**
     * @debug
     */
    public function version_modAct() {
        $data     = $this->validate(
            [
                'file_id' => 'required|integer',
                'node_id' => 'required|integer',
            ]);
        $nodeInfo = Node::where('id', $data['node_id'])->first();
        if (empty($nodeInfo)) return $this->apiErr(5701, 'node not found');
        $fileInfo = FileModel::where('id', $data['file_id'])->first();
        if (empty($fileInfo)) return $this->apiErr(5702, 'file not found');
        AssocNodeFile::where('id_node', $data['node_id'])->update(['status' => 0]);
        $ifExs = AssocNodeFile::where('id_node', $data['node_id'])->where('id_file', $data['file_id'])->
        select(['id_node']);
        if ($ifExs) AssocNodeFile::where('id_node', $data['node_id'])->where('id_file', $data['file_id'])->
        update(['status' => 1]);
        else AssocNodeFile::ignore()->insert(
            [
                'id_node' => $data['node_id'],
                'id_file' => $data['file_id'],
                'status'  => 1,
            ]);
        return $this->apiRet();
    }

    /**
     */
    public function file_deleteAct() {
        //解除文件和node对应的关系,如果文件已经没有node了，彻底删除文件
        $data     = $this->validate(
            [
                'file_id' => 'required|integer',
                'node_id' => 'required|integer',
            ]);
        $nodeInfo = Node::where('id', $data['node_id'])->first(['id']);
        if (empty($nodeInfo)) return $this->apiErr(5801, 'node not found');
        $fileInfo = FileModel::where('id', $data['file_id'])->first();
        if (empty($fileInfo)) return $this->apiErr(5802, 'file not found');
        $assocInfo = AssocNodeFile::where('id_file', $data['file_id'])->
        select();
        //扫描文件对应的节点
        //只有一个节点
        //      文件不是节点的当前版本
        //          *删除文件 *删除关联
        //      文件是节点的当前版本
        //          *删除文件 *删除关联并修改关联到文件id最大的文件
        //存在多个节点
        //      文件不是节点的当前版本
        //          *删除关联
        //      文件是节点的当前版本
        //          *删除关联并修改关联到文件id最大的文件
        //扫描修改过的节点文件
        //如果节点已经没有其他文件了 *删除节点
        $delFile  = false;
        $modAssoc = false;
        if (sizeof($assocInfo) == 1) {
            $delFile = true;
        }
        foreach ($assocInfo as $assoc) {
            if ($assoc['id_node'] == $data['node_id'] && $assoc['status'] == 1) {
                $modAssoc = true;
                break;
            }
        }
        if ($delFile) {
//            var_dump($fileInfo);
            @unlink(FileModel::getPathFromHash(
                $fileInfo->hash, $fileInfo->suffix,
                $fileInfo->type, 'raw', true
            ));
            @unlink(FileModel::getPathFromHash(
                $fileInfo->hash, $fileInfo->suffix_normal,
                $fileInfo->type, 'normal', true
            ));
            @unlink(FileModel::getPathFromHash(
                $fileInfo->hash, $fileInfo->suffix_preview,
                $fileInfo->type, 'preview', true
            ));
            FileModel::where('id', $fileInfo->id)->delete();
        }
        AssocNodeFile::where('id_node', $data['node_id'])->
        where('id_file', $data['file_id'])->
        delete();
        $assocNodeList = AssocNodeFile::where('id_node', $data['node_id'])->
        select();
        if (empty($assocNodeList)) {
            //删除孤儿节点
            Node::where('id', $data['node_id'])->delete();
        } elseif ($modAssoc) {
            //修改节点到新的文件关联
            $lastAssoc = end($assocNodeList);
            AssocNodeFile::where('id_node', $data['node_id'])->
            where('id_file', $lastAssoc['id_file'])->
            update(['status' => 1]);
            AssocNodeFile::where('id_file', $data['file_id'])->
            where('id_node', $data['node_id'])->delete();
        } else {
            //直接删除关联
            AssocNodeFile::where('id_file', $data['file_id'])->
            where('id_node', $data['node_id'])->delete();
        }
        return $this->apiRet();
    }

    public function tag_associateAct() {
        $data  = $this->validate(
            [
                'id'  => 'required|integer',
                'tag' => 'required|array',
            ]);
        $ifExs = Node::where('id', $data['id'])->selectOne();
        if (empty($ifExs)) return $this->apiErr(5801, 'node not found');
        Node::where('id', $data['id'])->update(
            [
                'list_tag_id' => implode(',', array_filter($data['tag'])),
            ]
        );
        \Job\Kernel::push(Index::class, $data['id']);
        return $this->apiRet($data['id']);
    }

}
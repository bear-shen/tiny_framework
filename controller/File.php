<?php namespace Controller;

use Lib\DB;
use Lib\GenFunc;
use Lib\ORM;
use Model\Node;

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
        ORM::$logging=true;
        $indexList = ORM::table('node as nd')->
        leftJoin('node_index as ni', 'nd.id', 'ni.id')->
        where(function ($query) use ($data) {
            /** @var $query ORM */
            switch ($data['method']) {
                default:
                case 'directory':
                    $query->where('nd.id_parent', $data['target']);
                    break;
                case 'tag':
                    $str = ORM::ormQuote($data['target']);
                    $query->whereRaw("FIND_IN_SET($str,ni.list_tag)");
                    break;
                case 'keyword':
                    $str = ORM::ormQuote($data['target']);
                    $query->whereRaw("MATCH(ni.index)against($str in boolean mode)");
                    break;
            }
        })->where('nd.status', $status[0], $status[1])->
        page($data['page'])->order($sort[0], $sort[1])->
        select(['ni.id', 'ni.list_tag',]);
        $idList    = array_column($indexList, 'id');
        //$idArrList = array_flip($idList);
//        var_dump($idList);
//        var_dump(ORM::$log);
        if (empty($idList)) return $this->apiRet([]);

        //-------------------------
        $nodeList      = ORM::table('node as nd')->
        leftJoin('node_info as ni', 'nd.id', 'ni.id')->
        whereIn('nd.id', $idList)->
        select(
            [
                'nd.id',
                'nd.id_parent',
                'nd.status',
                'nd.sort',
                'nd.is_file',
                'nd.time_create',
                'nd.time_update',
                'ni.name',
                'ni.description',
                'ni.id_cover',
            ]
        );
        $fileIdList    = [];
        $assocNodeList = [];
        foreach ($nodeList as $node) {
            $fileIdList[] = $node['id'];
            if ($node['id_cover']) {
                $fileIdList[] = $node['id_cover'];
            }
            $assocNodeList[$node['id']] = $node;
        }

        //-------------------------
        $fileList      = ORM::table('file as fl')->
        leftJoin('assoc_node_file as nf', 'fl.id', 'nf.id_file')->
        whereIn('nf.id_node', $fileIdList)->
        where('status', 1)->
        select(
            [
                'fl.id',
                'fl.hash',
                'fl.suffix',
                'fl.type',
                'fl.size',
                'nf.id_node',
                'nf.status',
            ]
        );
        $assocFileList = [];
        foreach ($fileList as $file) {
            $assocFileList[$file['id_node']] = $file;
        }

        //-------------------------
        $tagIdList        = [];
        $assocNodeTagList = [];
        foreach ($indexList as $index) {
            $nodeTagList = explode(',', $index['list_tag']);
            $nodeTagList = array_filter($nodeTagList);
            foreach ($nodeTagList as $tagId) {
                $tagIdList[] = $tagId;
            }
            $assocNodeTagList[$index['id']] = $nodeTagList;
        }
        $assocTagInfoList = [];
        if (!empty($tagIdList)) {
            $tagIdList        = array_keys(array_flip($tagIdList));
            $tagInfoList      = ORM::table('tag as tg')->leftJoin('tag_info as ti', 'tg.id', 'ti.id')->
            where('tg.status', 1)->
            whereIn('tg.id', $tagIdList)->select(
                [
                    'tg.id',
                    'tg.id_group',
                    //'tg.status',
                    //'tg.time_create',
                    //'tg.time_update',
                    'ti.name',
                    //'ti.alt',
                    //'ti.description',
                ]
            );
            $assocTagInfoList = GenFunc::value2key($tagInfoList, 'id');
            //
            $tagGroupList      = ORM::table('tag_group as tg')->leftJoin('tag_group_info as ti', 'tg.id', 'ti.id')->
            where('tg.status', 1)->
            whereIn('tg.id', $tagIdList)->select(
                [
                    'tg.id',
                    'tg.id_node',
                    //'tg.status',
                    //'tg.time_create',
                    //'tg.time_update',
                    'ti.name',
                    //'ti.alt',
                    //'ti.description',
                ]
            );
            $assocTagGroupList = GenFunc::value2key($tagGroupList, 'id');
            foreach ($assocTagInfoList as $tagId => $tagInfo) {
                $curGroup                 = ($assocTagGroupList[$tagInfo['id_group']] ?? []) + [
                        'id'      => '',
                        'id_node' => '',
                        'name'    => '',
                    ];
                $assocTagInfoList[$tagId] += [
                    'group_id'         => $curGroup['id'],
                    'group_id_node'    => $curGroup['id_node'],
                    'group_group_name' => $curGroup['group_name'],
                ];
            }
        }

        //-------------------------
        $assocTargetList = [];
        foreach ($indexList as $index) {
            $curNode       = ($assocNodeList[$index['id']] ?? []) + [
                    'id'          => '',
                    'id_parent'   => '',
                    'status'      => '',
                    'sort'        => '',
                    'is_file'     => '',
                    'time_create' => '',
                    'time_update' => '',
                    'name'        => '',
                    'description' => '',
                    'id_cover'    => '',
                ];
            $curFile       = ($assocFileList[$index['id']] ?? []) + [
                    'id'      => '',
                    'hash'    => '',
                    'suffix'  => '',
                    'type'    => '',
                    'size'    => '',
                    'id_node' => '',
                    'status'  => '',
                ];
            $curCover      = ($assocFileList[$curNode['id_cover']] ?? []) + [
                    'id'      => '',
                    'hash'    => '',
                    'suffix'  => '',
                    'type'    => '',
                    'size'    => '',
                    'id_node' => '',
                    'status'  => '',
                ];
            $assocTargetList
            [$index['id']] =
                [
                    'id'          => $curNode['id'],
                    'raw'         => Node::getPath($curFile['hash'], $curFile['suffix'], $curFile['type'], 'raw', false),
                    'normal'      => Node::getPath($curFile['hash'], $curFile['suffix'], $curFile['type'], 'normal', false),
                    'cover'       => $curCover['id'] ? Node::getPath($curCover['hash'], $curCover['suffix'], $curCover['type'], 'preview', false) : '',
                    'cover_id'    => $curNode['id_cover'],
                    'title'       => $curNode['name'],
                    'description' => $curNode['description'],
                    'size'        => $curFile['size'],
                    'hash'        => $curFile['hash'],
                    'type'        => $curNode['is_file'] ? $curFile['type'] : 'folder',
                    'favourite'   => $curNode['status'] == 2 ? 1 : 0,
                    'time_create' => $curNode['time_create'],
                    'time_update' => $curNode['time_update'],
                    //tag id
                    'tag'         => [],
                ];
            $nodeTags      = $assocNodeTagList[$index['id']] ?? [];
//            var_dump($assocNodeTagList);
            foreach ($nodeTags as $tagId) {
                $tag = ($assocTagInfoList[$tagId] ?? []) + [
                        'id'               => '',
                        'id_node'          => '',
                        'name'             => '',
                        'group_id'         => '',
                        'group_id_node'    => '',
                        'group_group_name' => '',
                    ];
                if (empty($assocTargetList[$index['id']]['tag'][$tag['group_id']])) {
                    $assocTargetList[$index['id']]['tag'][$tag['group_id']] = [
                        'id'   => '',
                        'name' => '',
                        'sub'  => [],
                    ];
                }
                $assocTargetList[$index['id']]['tag'][$tag['group_id']]['sub'][] = [
                    'id'   => $tag['id'],
                    'name' => $tag['name'],
                ];
            }
            $assocTargetList[$index['id']]['tag'] = array_values(
                $assocTargetList[$index['id']]['tag']
            );
            //explode(',', $index['list_tag'])
        }
        return $this->apiRet(array_values($assocTargetList));
    }

    public function modAct() {
    }

    public function moveAct() {
    }

    public function deleteAct() {
    }

}
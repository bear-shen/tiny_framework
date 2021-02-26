<?php namespace Controller;

use Lib\DB;
use Lib\ORM;
use Model\Node;

class File extends Kernel {
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
        select(['id', 'list_tag',]);
        $idList    = array_column($indexList, 'id');
        //$idArrList = array_flip($idList);
        $nodeList      = ORM::table('node as nd')->
        leftJoin('node_info as ni')->
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
        //
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
        //
        $assocTargetList = [];
        foreach ($indexList as $index) {
            $curNode       = $assocNodeList[$index['id']] ?? [
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
            $curFile       = $assocFileList[$index['id']] ?? [
                    'id'      => '',
                    'hash'    => '',
                    'suffix'  => '',
                    'type'    => '',
                    'size'    => '',
                    'id_node' => '',
                    'status'  => '',
                ];
            $curCover      = $assocFileList[$curNode['id_cover']] ?? [
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
                    'raw'         => '',
                    'normal'      => '',
                    'cover'       => '',
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
            //explode(',', $index['list_tag'])
        }

    }

    public function modAct() {
    }

    public function moveAct() {
    }

    public function deleteAct() {
    }

}
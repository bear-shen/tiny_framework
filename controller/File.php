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
//        ORM::$logging=true;
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
        select(['ni.id',]);
        $idList    = array_column($indexList, 'id');
        //$idArrList = array_flip($idList);
//        var_dump($idList);
//        var_dump(ORM::$log);
        //if (empty($idList)) return $this->apiRet([]);
        $crumbIdList = [];
        if ($data['method'] == 'directory') {
            //$data['target'] = intval($data['target']);
            $idList[] = $data['target'];
            $crumb    = ORM::table('node_index')->
            where('id', $data['target'])->
            first(['list_node']);
            if (!empty($crumb)) {
                $crumbIdList = explode(',', $crumb['list_node']);
                foreach ($crumbIdList as $crumbId)
                    $idList[] = $crumbId;
            }
        }
        $nodeInfoList = Node::nodeInfoList($idList);
        //
        $list = [];
        $dir  = [];
        $navi = [
            ['id' => 0, 'name' => 'root', 'type' => 'directory',]
        ];
        foreach ($nodeInfoList as $nodeInfo) {
            //@todo 这里应该要排个序
            if (in_array($nodeInfo['id'], $crumbIdList)) {
//                var_dump($nodeInfo);
                $navi[] = ['id' => $nodeInfo['id'], 'name' => $nodeInfo['title'], 'type' => 'directory',];
                continue;
            }
            if ($nodeInfo['id'] == $data['target']) {
                $dir    = $nodeInfo;
                $navi[] = ['id' => $nodeInfo['id'], 'name' => $nodeInfo['title'], 'type' => 'directory',];
                continue;
            }
            $list[] = $nodeInfo;
        }

        return $this->apiRet(
            [
                'list' => $list,
                'navi' => $navi,
                'dir'  => $dir,
            ]);
    }

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

    public function coverAct() {
        $data   = $this->validate(
            [
                'id'            => 'required|integer',
                //id_cover目前存的是file的id
                'node_cover_id' => 'required|integer',
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

    public function moveAct() {

    }

    public function deleteAct() {
    }

    public function uploadAct() {
    }

    public function favouriteAct() {
        $data   = $this->validate(
            [
                'id' => 'required|integer',
            ]);
        $ifNode = ORM::table('node')->
        where('id', $data['id'])->first();
        if (!$ifNode) return $this->apiErr(5201, 'node not found');
        $node = ORM::table('node')->
        where('id', $data['id'])->
        update(['status' => 2]);
        return $this->apiRet($data['id']);
    }

    public function recoverAct() {
    }

    public function delete_foreverAct() {
    }

    public function mkdirAct() {
    }

    public function versionAct() {
    }

    public function version_modAct() {
    }

}
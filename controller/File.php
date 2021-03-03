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
        if (empty($idList)) return $this->apiRet([]);

        return $this->apiRet(
            [
                'list' => [],
                'navi' => [],
                'dir'  => [],
            ]);
    }

    public function modAct() {
    }

    public function moveAct() {
    }

    public function deleteAct() {
    }

}
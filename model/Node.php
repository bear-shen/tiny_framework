<?php namespace Model;

use Lib\DB;
use Lib\GenFunc;
use Lib\ORM;

class Node {
    /**
     * 生成传入id的面包屑导航
     * @param array $nodeIdList [1,2,3] or 1
     * @return array
     * [[
     *      'name'  =>['root','path1','path2'],
     *      'id'    =>[1,2,3],
     * ]]
     */
    public static function crumb($nodeIdList) {
        if (!is_array($nodeIdList)) $nodeIdList = [$nodeIdList];
        $treeList        = ORM::table('node_index')->whereIn('id', $nodeIdList)->select(
            ['id', 'list_node as tree']
        );
        $totalNodeIdList = [];
        $nodeIdAssoc     = [];
        foreach ($treeList as $tree) {
            $totalNodeIdList[]        = $tree['id'];
            $nodeIdAssoc[$tree['id']] = [];
            $subs                     = explode(',', $tree['tree']);
            foreach ($subs as $sub) {
                $totalNodeIdList[]          = $sub;
                $nodeIdAssoc[$tree['id']][] = $sub;
            }
        }
        $totalNodeIdList = array_keys(array_flip($totalNodeIdList));
        /*$nodeInfoList    = ORM::table('node nd')->
        leftJoin('node_info ni', 'nd.id', 'ni.id')->
        whereIn('nd.id', $totalNodeIdList)->select(
            [
                'nd.id',
                'nd.id_parent',
                'nd.status',
                'nd.sort',
                'nd.is_file',
                'nd.time_create',
                'nd.time_update',
                //'ni.id',
                'ni.name',
                'ni.description',
                'ni.id_file_cover',
            ]
        );*/
        $nodeInfoList  = ORM::table('node_info')->
        whereIn('id', $totalNodeIdList)->select(
            [
                'id',
                'name',
            ]
        );
        $nodeInfoAssoc = [];
        foreach ($nodeInfoList as $nodeInfo) {
            $nodeInfoAssoc[$nodeInfo['id']] = $nodeInfo;
        }
        //
        $crumb = [];
        foreach ($nodeIdAssoc as $node => $subs) {
            $cur = [
                'id'   => [],
                'name' => [],
            ];
            foreach ($subs as $sub) {
                if ($sub == 0) {
                    $cur['id'][]   = 0;
                    $cur['name'][] = 'root';
                    continue;
                }
                $cur['id'][]   = $nodeInfoAssoc[$sub]['id'];
                $cur['name'][] = $nodeInfoAssoc[$sub]['name'];
            }
            $cur['id'][]   = $nodeInfoAssoc[$node]['id'];
            $cur['name'][] = $nodeInfoAssoc[$node]['name'];
            $crumb[]       = $cur;
        }
        return $crumb;
    }

    //select * from node_index where match(`index`) against ('folder' IN BOOLEAN MODE);

    /**
     * 排序方法
     * @param string $sort
     * @return string[]
     */
    public static function availSort($sort = '') {
        $target = [];
        switch ($sort) {
            default:
            case 'id_asc':
                $target = ['id', 'asc'];
                break;
            case 'id_desc':
                $target = ['id', 'desc'];
                break;
            case 'name_asc':
                $target = ['name', 'asc'];
                break;
            case 'name_desc':
                $target = ['name', 'desc'];
                break;
            case 'crt_asc':
                $target = ['time_create', 'asc'];
                break;
            case 'crt_desc':
                $target = ['time_create', 'desc'];
                break;
            case 'upd_asc':
                $target = ['time_update', 'asc'];
                break;
            case 'upd_desc':
                $target = ['time_update', 'desc'];
                break;
        }
        return $target;
    }

    public static function availStatus($status) {
        $target = [];
        switch ($status) {
            default:
            case 'list':
                $target = ['!=', 0];
                break;
            case 'favourite':
                $target = ['=', 2];
                break;
            case 'recycle':
                $target = ['=', 0];
                break;
        }
        return $target;
    }
}
<?php namespace Model;

use Lib\DB;
use Lib\GenFunc;
use Lib\ORM;

class Node {
    /**
     * @param array $nodeIdList [1,2,3] or 1
     * @return array
     * [[
     *      'name'  =>['root','path1','path2'],
     *      'id'    =>[1,2,3],
     * ]]
     */
    public static function crumb($nodeIdList) {
        if (!is_array($nodeIdList)) $nodeIdList = [$nodeIdList];
        $treeList        = ORM::table('node_tree')->whereIn('id', $nodeIdList)->select(
            ['id', 'tree']
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
}
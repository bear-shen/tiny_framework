<?php namespace Job;

use Lib\DB;
use Lib\GenFunc;
use Lib\Request;
use Model\File;
use Model\Node;
use Model\Tag;
use Model\TagGroup;

class Index {
    public function handle($nodeId) {
        //目标是尽可能的短，所以写的简略
        /*$indexStructure = [
             [
                'group_name',
                'group_alt',
                'group_description',
                [
                    'name', 'alt', 'description',
                    'name', 'alt', 'description',
                ],
            ],
            [
                'name'        ,
                'description' ,
                'root/dir1/dir2/dir3',
            ],
        ];*/
        $curNode = Node::where('id', $nodeId)->selectOne(
            ['id', 'name', 'description', 'list_tag_id', 'list_node',]
        );
        if (empty($curNode)) return false;
        //
        $tagIndex = [];
        if (!empty($curNode['list_tag_id'])) {
            $tagList       = Tag::whereIn('id', explode(',', $curNode['list_tag_id']))->select(
                [
                    'id',
                    'id_group',
                    'name',
                    'alt',
                    'description',
                ]);
            $tagAssocByGid = [];
            $groupIdList   = [];
            foreach ($tagList as $tag) {
                if (empty($tagAssocByGid[$tag['id_group']])) $tagAssocByGid[$tag['id_group']] = [];
                $tagAssocByGid[$tag['id_group']][] = [
                    $tag['name'],
                    $tag['alt'],
                    $tag['description'],
                ];
                $groupIdList[]                     = $tag['id_group'];
            }
            $groupList = TagGroup::whereIn('id', $groupIdList)->select(
                [
                    'id',
                    'name',
                    'alt',
                    'description',
                ]
            );
            foreach ($groupList as $group) {
                $tagIndex[] = [
                    $group['name'],
                    $group['alt'],
                    $group['description'],
                    $tagAssocByGid[$group['id']]
                ];
            }
        }
        //
        $treeIndex  = [];
        $nodeIdList = explode(',', $curNode['list_node']);
        $nodeList   = Node::whereIn('id', $nodeIdList)->select(
            [
                'id',
                'name',
                //                'description',
            ]);
        $nodeAssoc  = [];
        foreach ($nodeList as $node) {
            $nodeAssoc[$node['id']] = $node['name'];
        }
        foreach ($nodeIdList as $nodeId) {
            $treeIndex = $nodeId == '0' ? 'root' : $nodeAssoc[$nodeId];
        }
        $indexStructure = [
            $tagIndex,
            [
                $curNode['name'],
                $curNode['description'],
                implode('/', $treeIndex),
            ],
        ];
        Node::where('id', $nodeId)->update(
            [
                '`index`' => json_encode($indexStructure, JSON_UNESCAPED_UNICODE)
            ]
        );
        return true;
    }

}
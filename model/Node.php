<?php namespace Model;

use Lib\DB;
use Lib\GenFunc;
use Lib\ORM;

class Node {
    public static function nodeInfoList($nodeIdList = [0], $withTag = true) {
        if (!is_array($nodeIdList)) {
            $nodeIdList = [$nodeIdList];
        }
        //var_dump($nodeIdList);
        //-------------------------
        $nodeList      = ORM::table('node as nd')->
        leftJoin('node_info as ni', 'nd.id', 'ni.id')->
        whereIn('nd.id', $nodeIdList)->
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
            if ($node['is_file'])
                $fileIdList[] = $node['id'];
            if ($node['id_cover'])
                $fileIdList[] = $node['id_cover'];
            $assocNodeList[$node['id']] = $node;
        }
//        var_dump($nodeList);

        //-------------------------
        $assocFileList = [];
        if (!empty($fileIdList)) {
            $fileList = ORM::table('file as fl')->
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
            foreach ($fileList as $file) {
                $assocFileList[$file['id_node']] = $file;
            }
        }
//        var_dump($fileList);

        //-------------------------

        $assocNodeTagList = [];
        $assocTagInfoList = [];
        if ($withTag) {
            $tagIdList = [];
            $indexList = ORM::table('node_index')->
            whereIn('id', $nodeIdList)->select(['id', 'list_tag',]);
            foreach ($indexList as $index) {
                $nodeTagList = explode(',', $index['list_tag']);
                $nodeTagList = array_filter($nodeTagList);
                foreach ($nodeTagList as $tagId) {
                    $tagIdList[] = $tagId;
                }
                $assocNodeTagList[$index['id']] = $nodeTagList;
            }
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
                whereIn('tg.id', array_column($tagInfoList, 'id_group'))->select(
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
//                var_dump($assocTagGroupList);
//                var_dump($assocTagGroupList);
                foreach ($assocTagInfoList as $tagId => $tagInfo) {
                    $curGroup                 = ($assocTagGroupList[$tagInfo['id_group']] ?? []) + [
                            'id'      => '',
                            'id_node' => '',
                            'name'    => '',
                        ];
                    $assocTagInfoList[$tagId] += [
                        'group_id'   => $curGroup['id'],
                        'group_node' => $curGroup['id_node'],
                        'group_name' => $curGroup['name'],
                    ];
                }
            }
        }

        //-------------------------
        $assocTargetList = [];
//        var_dump($nodeList);
        foreach ($nodeList as $index) {
//            var_dump($index['id']);
//            var_dump($assocNodeList[$index['id']]);
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
            if (!$withTag) continue;
            $nodeTags = $assocNodeTagList[$index['id']] ?? [];
//            var_dump($assocNodeTagList);
//            var_dump($nodeTags);
//            var_dump($assocTagInfoList);
            foreach ($nodeTags as $tagId) {
                $tag = ($assocTagInfoList[$tagId] ?? []) + [
                        'id'         => '',
                        'id_node'    => '',
                        'name'       => '',
                        'group_id'   => '',
                        'group_node' => '',
                        'group_name' => '',
                    ];
                if (empty($assocTargetList[$index['id']]['tag'][$tag['group_id']])) {
                    $assocTargetList[$index['id']]['tag'][$tag['group_id']] = [
                        'id'   => $tag['group_id'],
                        'name' => $tag['group_name'],
                        'sub'  => [],
                    ];
                }
                $assocTargetList[$index['id']]['tag'][$tag['group_id']]['sub'][] = [
                    'id'   => $tag['id'],
                    'name' => $tag['name'],
                ];
            }
            if (!empty($assocTargetList[$index['id']]['tag']))
                $assocTargetList[$index['id']]['tag'] =
                    array_values($assocTargetList[$index['id']]['tag']);
            //explode(',', $index['list_tag'])
        }
//        var_dump($assocTargetList);
        return array_values($assocTargetList);
    }

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
                $target = ['nd.id', 'asc'];
                break;
            case 'id_desc':
                $target = ['nd.id', 'desc'];
                break;
            case 'name_asc':
                $target = ['ni.name', 'asc'];
                break;
            case 'name_desc':
                $target = ['ni.name', 'desc'];
                break;
            case 'crt_asc':
                $target = ['nd.time_create', 'asc'];
                break;
            case 'crt_desc':
                $target = ['nd.time_create', 'desc'];
                break;
            case 'upd_asc':
                $target = ['nd.time_update', 'asc'];
                break;
            case 'upd_desc':
                $target = ['nd.time_update', 'desc'];
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


    /**
     * @see http://svn.apache.org/repos/asf/httpd/httpd/trunk/docs/conf/mime.types
     * @see https://www.php.net/manual/zh/function.mime-content-type.php
     *
     * 根据 mime 判断文件类型
     *
     * 这里有一些问题， mime 是服务器自己获取的，实际上不具有校验的功能
     * 那么还不如干脆不要获取 mime ，而且过程式用法看起来也不够 “fashion”
     * 不过话说加入 spl 似乎让流程更麻烦了，还不如老办法一把梭
     */
    /*public static $mime   = [
        'video/mp4'       => ['type' => 'video', 'suffix' => 'mp4'],
        'video/ogg'       => ['type' => 'video', 'suffix' => 'ogg'],
        'video/3gpp'      => ['type' => 'video', 'suffix' => '3gp'],
        'video/mpeg'      => ['type' => 'video', 'suffix' => 'mpg'],
        'video/webm'      => ['type' => 'video', 'suffix' => 'webm'],
        'video/quicktime' => ['type' => 'video', 'suffix' => 'mov'],
        'audio/x-aac'     => ['type' => 'audio', 'suffix' => 'aac'],
        'audio/mp4'       => ['type' => 'audio', 'suffix' => 'm4a'],
        'audio/mpeg'      => ['type' => 'audio', 'suffix' => 'mp3'],
        'audio/x-flac'    => ['type' => 'audio', 'suffix' => 'flac'],
        'audio/webm'      => ['type' => 'audio', 'suffix' => 'weba'],
        'image/jpeg'      => ['type' => 'image', 'suffix' => 'jpg'],
        'image/gif'       => ['type' => 'image', 'suffix' => 'gif'],
        'image/png'       => ['type' => 'image', 'suffix' => 'png'],
        'image/bmp'       => ['type' => 'image', 'suffix' => 'bmp'],
        'image/apng'      => ['type' => 'image', 'suffix' => 'apng'],
        'image/webp'      => ['type' => 'image', 'suffix' => 'webp'],
    ];*/

    public static $suffix = [
        'log'  => 'text',
        'txt'  => 'text',
        'xml'  => 'text',
        //
        'zip'  => 'binary',
        'rar'  => 'binary',
        'iso'  => 'binary',
        //
        'mp4'  => 'video',
        'ogg'  => 'video',
        '3gp'  => 'video',
        'mpg'  => 'video',
        'webm' => 'video',
        'mov'  => 'video',
        'wmv'  => 'video',
        //
        'aac'  => 'audio',
        'm4a'  => 'audio',
        'mp3'  => 'audio',
        'wav'  => 'audio',
        'flac' => 'audio',
        'weba' => 'audio',
        //
        'jpg'  => 'image',
        'gif'  => 'image',
        'png'  => 'image',
        'bmp'  => 'image',
        'apng' => 'image',
        'webp' => 'image',
    ];
    /**
     * use with define(FILE_*)
     */
    public static $path = [
        'pub' => 'upload',
        'tmp' => 'tmp',
    ];

    /**
     * 获取文件类型，返回数组为 [文件类型,文件后缀]
     * @param $fileName string
     * @return array [type,suffix]
     */
    public static function getType($fileName) {
        $suf = self::getSuffix($fileName);
//        var_dump($suf);
        $suf = strtolower($suf);
        if (isset(self::$suffix[$suf])) return [self::$suffix[$suf], $suf];
        return ['binary', $suf];
    }

    /**
     * 提取文件后缀
     * 这里不做其他的处理了，就提取一下后缀，写入文件时有用
     * @param $fileName string
     * @return string
     */
    public static function getSuffix($fileName) {
        $pos  = mb_strrpos($fileName, '.');
        $subS = mb_substr($fileName, $pos + 1);
        if (mb_strrpos($fileName, DIRECTORY_SEPARATOR) !== false) $subS = '';
        return $subS;
    }


    /**
     * 根据 hash 生成对应的文件路径
     * 与其叫 getPath 不如叫 makePath 。。。无所谓不过
     * @param string $hash 可以是文件的 hash 也可以是文件名的 hash ，方便临时文件写入
     * @param string $suffix
     * @param string $type 文件类型
     * @param boolean $local 本地文件标识
     * @param string $level 文件分层 preview normal raw temp
     *
     * 文件结构:
     * local/level/type/hashPath.suffix
     *
     * @return string
     */
    public static function getPath($hash, $suffix = '', $type = '', $level = 'raw', $local = false) {
        $s = $local ? DIRECTORY_SEPARATOR : '/';
        //
        $root = '';
        if ($local) {
            $root = FILE_ROOT;
        } else {
            $root = SCHEME . '://' . FILE_DOMAIN;
        }
        //
        $directory = '';
        switch ($level) {
            default:
            case 'temp':
                $directory = self::$path['tmp'];
                break;
            case 'preview':
            case 'normal':
            case 'raw':
                $directory = self::$path['pub'];
                break;
        }
        //
        $subPath =
            substr($hash, 0, 1) . $s .
            substr($hash, 1, 2) . $s .
            substr($hash, 3, 2) . $s .
            substr($hash, 5) .
            (empty($suffix) ? '' : ('.' . $suffix));
        //
        $target = rtrim($root, $s)
                  . $s . $directory
                  . $s . $level
                  . $s . $type
                  . $s . ltrim($subPath, $s);
        return $target;
    }

    /**
     * 生成文件 hash
     * @param string $path
     * @return false|string
     */
    public static function getHash($path) {
        $md5 = md5_file($path);
        return substr($md5, 8, 16);
    }
}
<?php namespace Model;

use Lib\DB;
use Lib\GenFunc;
use Lib\ORM;

class Node {
    /**
     * @var $user array
     * ['id'=>'','id_group'=>'','name'=>'','status'=>''...]
     */
    private $user = [];

    public function __construct($user = []) {
        $this->user = $user;
    }

    //------------------ zzz ------------------

    /**
     * mod file
     * @param $input array
     * [
     *    'id'          =>0,
     *    'id_parent'   =>0,
     * // when mod file
     *    'hash'        =>'',
     *    'suffix'      =>'',
     *    'size'        =>0,
     *    'type'        =>'',//'audio','video','image','binary','text','folder'
     * // when mod node info
     *    'name'        =>'',
     *    'description' =>'',
     *    'id_file_cover' =>'',
     * ]
     * @return array
     */
    public function mod($input) {
        $input += [
            'id'            => 0,//node_id
            'id_parent'     => 0,
            //
            'hash'          => '',
            'suffix'        => '',
            'size'          => 0,
            'type'          => '',
            //
            'name'          => '',
            'description'   => '',
            'id_file_cover' => 0,
        ];
        //check, base data
        $isNewNode     = true;
        $ifParentExist = DB::queryGetOne('select id,is_file from node where id=:id', ['id' => $input['id_parent']]);
        if (empty($ifParentExist)) $input['id_parent'] = 0;
        else {
            if ($ifParentExist['is_file']) return [1, 'parent is not a folder'];
        }
        //获取node
        $node = [];
        if (!empty($input['id'])) {
            $node = DB::queryGetOne('select id,id_parent,is_file from node where id=:id', ['id' => $input['id']]);
        } else {
            //添加node时需要增加id
            if (empty($input['name']) || empty($input['type']) || empty($input['hash']))
                return [1, 'need param:name,type'];
            if ($input['type'] != 'folder' && empty($input['hash']))
                return [1, 'file hash failed'];
            $isNewNode = true;
            $node      = [
                'id_parent' => $input['id_parent'],
                'status'    => 1,
                'sort'      => 0,
                'is_file'   => $input['type'] == 'folder' ? 0 : 1,
            ];
            DB::execute('insert into node(id_parent, status, sort, is_file) value (:id_parent, :status, :sort, :is_file);', $node);
            $nodeId     = DB::lastInsertId();
            $node['id'] = $nodeId;
        }
        if (empty($node)) return [1, 'node not found'];
        //检查文件关联
        /*$fileAssoc = false;
        $fileInfo  = false;
        if ($node['is_file']) {
            $fileAssoc = DB::queryGetOne('select id_node,id_file from assoc_node_file where status=1 and id_node=:id;', ['id' => $input['id']]);
            if (empty($fileAssoc)) return [1, 'no file associated to this node'];
            //
            $fileInfo = DB::queryGetOne('select id,hash from file where id=:id;', ['id' => $fileAssoc['id_file']]);
            if (empty($fileInfo)) return [1, 'file association broken'];
        }*/
        //更新节点数据
        $nodeInfo = DB::queryGetOne('select * from node_info where id=:id', ['id' => $node['id']]);
        if (
            !empty($input['name']) &&
            (empty($nodeInfo) || $input['name'] != $nodeInfo['name'] || $input['description'] != $nodeInfo['description'] || $input['id_file_cover'] != $nodeInfo['id_file_cover'])
        ) {
            DB::execute(
                'replace into node_info (id, name, description, id_file_cover) VALUE (:id,:name,:description,:id_file_cover);',
                [
                    'id'            => $node['id'],
                    'name'          => $input['name'],
                    'description'   => $input['description'],
                    'id_file_cover' => $input['id_file_cover'],
                ]);
        }
        //更新parent
        if ($isNewNode || $node['id_parent'] != $input['id_parent']) {
            $curNodeTree = DB::queryGetOne('select id, tree from node_tree where id=:id_parent;', ['id_parent' => $input['id_parent']]);
            $tree        = [];
            if (!empty($curNodeTree)) {
                $tree = explode(',', $curNodeTree['tree']);
            }
            $tree[] = $input['id_parent'];
            DB::execute('replace into node_tree(id, tree) VALUE (:id, :tree)',
                        ['id' => $node['id'], 'tree' => implode(',', $tree)]
            );
            if (!$isNewNode) {
                DB::execute('update node set id_parent=:id_parent where id=:id;',
                            ['id_parent' => $input['id_parent'], 'id' => $node['id'],]
                );
                $node['id_parent'] = $input['id_parent'];
            }
        }
        //更新文件
        if ($node['is_file']) {
            $fileAssoc = false;
            $fileInfo  = false;
            $fileAssoc = DB::queryGetOne('select id_node,id_file from assoc_node_file where status=1 and id_node=:id;', ['id' => $node['id']]);
            if (!empty($fileAssoc)) {
                $fileInfo = DB::queryGetOne('select id,hash from file where id=:id;', ['id' => $fileAssoc['id_file']]);
            }
            //
            if (empty($fileInfo) || $fileInfo['hash'] != $input['hash']) {
                //检查是否有同hash的文件
                $targetFile = DB::queryGetOne('select * from file where hash=:hash', ['hash' => $input['hash']]);
                if (empty($targetFile)) {
                    $targetFileData = [
                        'hash'   => $input['hash'],
                        'suffix' => $input['suffix'],
                        'type'   => $input['type'],
                        'size'   => $input['size'],
                    ];
                    DB::execute('insert into file(hash, suffix, type, size) value(:hash,:suffix,:type,:size);', $targetFileData);
                    $fileId     = DB::lastInsertId();
                    $targetFile = $targetFileData + ['id' => $fileId];
                }
                $this->modNodeFileAssociate($node['id'], $targetFile['id']);
            }
        }
        //
        return [0, 'success'];
    }

    /**
     * 纸面删除
     * @param int $nodeId
     * @return array
     */
    public function del($nodeId) {
        DB::execute('update node set status=0 where id=:id', ['id' => $nodeId]);
        return [0, 'success'];
    }

    /**
     * 查看文件列表
     * @param int $nodeId
     * @param int $page
     * @param string $sort
     * 'id','time_create','time_update','name','size','suffix',
     * 可拼接'.asc'或'.desc'指示排序
     * @param int $pageSet
     * @return array
     * [
     *      'current'=>[
     *          'id'            =>'',
     *          'name'          =>'',
     *          'description'   =>'',
     *          'path'          =>'',//cover
     *          'tree'          =>[
     *              ['id'=>'','name'=>'',]
     *          ],
     *      ],
     *      'child'=>[[
     *          'id'            =>'',
     *          'name'          =>'',
     *          'description'   =>'',
     *          'is_file'       =>'',
     *          //file extra
     *          'path'          =>'',
     *          'type'          =>'',
     *          'suffix'        =>'',
     *          'size'          =>'',
     *      ],],
     * ]
     */
    public function list($nodeId = 0, $page = 1, $sort = 'id', $pageSet = 100) {
        //---------------------------------------------------
        $current = DB::queryGetOne('select 
nd.id,nd.is_file,nd.time_create,nd.time_update,nd.status,
ni.name,ni.description,ni.id_file_cover,
nt.tree
from node nd 
left join node_info ni on ni.id=nd.id
left join node_tree nt on ni.id=nt.id
where nd.id=:id;', ['id' => $nodeId]);
        if ($current['is_file']) {
            return [1, 'target is not folder'];
        }
        $currentDetail = $this->listDetail([$current['id']]);
        //
        $treeData = $this->getCrumb($current['tree']);
        //
        $currentData =
            GenFunc::array_only($current, ['id', 'name', 'description',])
            + ['path' => $currentDetail[$current['id']]['path']]
            + ['tree' => $treeData];
        //---------------------------------------------------
        $child = DB::query('select 
nd.id,nd.is_file,nd.time_create,nd.time_update,nd.status,
ni.name,ni.description,ni.id_file_cover
from node nd 
left join node_info ni on ni.id=nd.id
where nd.id_parent=:id;', ['id' => $nodeId]);
        $list  = [
            'dir'  => [],
            'file' => [],
        ];
        foreach ($child as $item) {
            if ($item['is_file']) {
                $list['file'][] = $item;
            } else {
                $list['dir'][] = $item;
            }
        }
        //写入文件夹预览
        $nodeIdList = [];
        foreach ($list['dir'] as $item) {
            if (empty($item['id_file_cover'])) continue;
            $nodeIdList[] = $item['id_file_cover'];
        }
        $assocList = $this->listDetail($nodeIdList);
        for ($i1 = 0; $i1 < sizeof($list['dir']); $i1++) {
            $id               = (string)$list['dir'][$i1]['id_file_cover'];
            $list['dir'][$i1] += $assocList[$id];
        }
        //写入文件详情
        $nodeIdList = [];
        foreach ($list['file'] as $item) {
            if (empty($item['id'])) continue;
            $nodeIdList[] = $item['id'];
        }
        $assocList = $this->listDetail($nodeIdList);
        for ($i1 = 0; $i1 < sizeof($list['file']); $i1++) {
            $id                = (string)$list['file'][$i1]['id'];
            $list['file'][$i1] += $assocList[$id];
        }
        //排序，文件夹和文件分别排序后合并到数组
        $sort = explode('.', $sort);
        $sc   = empty($sort[1]) ? 'asc' : $sort[1];
        if (!in_array($sort, ['id', 'time_create', 'time_update', 'name', 'size', 'suffix',]))
            $sort = 'id';
        foreach (['dir', 'file'] as $type) {
            usort($list[$type], function ($a, $b) use ($sort, $sc) {
                $spec = true;
                switch ($sort) {
                    case 'id':
                    case 'size':
                        $spec = $a[$sort] > $b[$sort];
                        break;
                    default:
                        $spec = strcmp($a[$sort], $b[$sort]);
                        break;
                }
                if ($sc == 'desc') $spec = !$spec;
                return $spec;
            });
        }
        //
        $target = [];
        foreach ($list['dir'] as $item) {
            $target[] = $item;
        }
        foreach ($list['file'] as $item) {
            $target[] = $item;
        }
        return [0, 'success', ['current' => $currentData, 'child' => $target]];
    }

    /**
     * @param int $nodeId
     * @param int $withVersion 0 -> current
     * @return array
     * [
     *      'id'            =>'',
     *      'name'          =>'',
     *      'description'   =>'',
     *      'is_file'       =>'',
     *      //file extra
     *      'path'          =>'',
     *      'type'          =>'',
     *      'suffix'        =>'',
     *      'size'          =>'',
     *      'tree'          =>[
     *          ['id'=>'','name'=>'',]
     *      ],
     *      'version'       =>[
     *          'id'            =>'',
     *          'is_current'    =>'',
     *          'time_update'   =>'',
     *          'time_create'   =>'',
     *          'size'          =>'',
     *      ]
     * ]
     */
    public function detail($nodeId, $withVersion = 0) {
        $targetData = [
            'id'          => '',
            'name'        => '',
            'description' => '',
            'is_file'     => '',
            //file extra
            'path'        => '',
            'type'        => '',
            'suffix'      => '',
            'size'        => '',
            'tree'        => [],
            'version'     => [],
        ];
        $node       = DB::queryGetOne('select 
nd.id,nd.is_file,nd.time_create,nd.time_update,nd.status,
ni.name,ni.description,ni.id_file_cover,
nt.tree
from node nd 
left join node_info ni on ni.id=nd.id
left join node_tree nt on ni.id=nt.id
where nd.id=:id;', ['id' => $nodeId]);
        if (!$node['is_file']) return [1, 'current node is not file'];
        $targetData = [
                          'id'          => $node['id'],
                          'name'        => $node['name'],
                          'description' => $node['description'],
                          'is_file'     => $node['is_file'],
                      ] + $targetData;
        //
        $targetData['tree'] = $this->getCrumb($node['tree']);
        //多版本的文件数据
        $assocList = DB::query('select id_file,status from assoc_node_file where id_node=:id;', ['id' => $nodeId]);
        $curFileId = 0;
        foreach ($assocList as $assoc) {
            if ($assoc['status'] == 1) {
                $curFileId = $assoc['id_file'];
                break;
            }
        }
        $fileList = DB::query(
            'select
id, hash, suffix, type, size, time_create, time_update
from file where id in (:v)', [], array_column($assocList, 'id_file')
        );
        foreach ($fileList as $file) {
            $isCurrent = 0;
            if (
                ($withVersion && $file['id'] == $withVersion)
                || (!$withVersion && $file['id'] == $curFileId)
            ) {
                $isCurrent  = 1;
                $targetData =
                    [
                        'type'   => $file['type'],
                        'suffix' => $file['suffix'],
                        'size'   => $file['size'],
                        'path'   => self::getPath(
                            $file['hash'],
                            $file['type'],
                            $file['suffix'],
                            'normal',
                            false,
                        ),
                    ] + $targetData;
            }
            $targetData['version'][] = [
                'id'          => $file['id'],
                'is_current'  => $isCurrent,
                'time_update' => $file['time_update'],
                'time_create' => $file['time_create'],
                'size'        => $file['size'],
            ];
        }
        return $targetData;
    }

    /**
     * 获取原始文件，因为基本就纯下载了，不添加过多参数
     * @param int $nodeId
     * @param int $withVersion
     * @return array
     */
    public function raw($nodeId, $withVersion = 0) {
        $targetData = [
            'id'          => '',
            'name'        => '',
            'description' => '',
            'is_file'     => '',
            //file extra
            'path'        => '',
            'type'        => '',
            'suffix'      => '',
            'size'        => '',
        ];
        $node       = DB::queryGetOne('select 
nd.id,nd.is_file,nd.time_create,nd.time_update,nd.status,
ni.name,ni.description,ni.id_file_cover
from node nd 
left join node_info ni on ni.id=nd.id
left join node_tree nt on ni.id=nt.id
where nd.id=:id;', ['id' => $nodeId]);
        $assocList  = DB::query('select id_file,status from assoc_node_file where id_node=:id;', ['id' => $nodeId]);
        $curFileId  = 0;
        foreach ($assocList as $assoc) {
            if (
                ($withVersion && $assoc['id_file'] == $withVersion)
                || (!$withVersion && $assoc['status'] == 1)
            ) {
                $curFileId = $assoc['id_file'];
            }
        }
        $file       = DB::queryGetOne(
            'select
id, hash, suffix, type, size, time_create, time_update
from file where id =:id', ['id' => $curFileId]);
        $targetData = [
                          'type'   => $file['type'],
                          'suffix' => $file['suffix'],
                          'size'   => $file['size'],
                          'path'   => self::getPath(
                              $file['hash'],
                              $file['type'],
                              $file['suffix'],
                              'raw',
                              false,
                          ),
                      ] + $targetData;
        return $targetData;
    }

// ----------------------------------------------------------------
// helper
// ----------------------------------------------------------------
    /**
     * 生成面包屑
     * @param string $tree
     * @return array
     * [
     *     ['id'=>'','name'=>'',]
     * ]
     */
    private function getCrumb($tree = '') {
        if (is_string($tree)) {
            $treeArr = explode(',', $tree);
        } else {
            $treeArr = $tree;
        }
        $crumbs   = DB::query('select id,name from node_info where id in (:v);', [], $treeArr);
        $crumbs   = GenFunc::col2key($crumbs, 'id');
        $treeData = [];
        foreach ($treeArr as $treeId) {
            $treeData[] = $crumbs[$treeId];
        }
        return $treeData;
    }

    /**
     * @param array|int $nodeIdList [1,2,3,4...] or 1
     * @param string $level 'preview','normal','raw'
     * @return array
     * [
     *      'nodeId'    =>[
     *          'type'      => 'string',
     *          'suffix'    => 'string',
     *          'size'      => 'int',
     *          'path'      => 'null|string',
     *      ],
     * ]
     */
    private function listDetail($nodeIdList = [], $level = 'preview') {
        if (!is_array($nodeIdList)) $nodeIdList = [$nodeIdList];
        $targetList = [];
        $assocList  = DB::query('select 
anf.id_node, fl.id, fl.hash, fl.suffix, fl.type, fl.size 
from assoc_node_file anf
left join file fl on fl.id=anf.id_file
where 
anf.id_node in (:v) 
and status=1
;', [], $nodeIdList);
        $assocList  = GenFunc::col2key($assocList, 'id_node');
        foreach ($nodeIdList as $nodeId) {
            $id = (string)$nodeId;
            if (empty($assocList[$id])) {
                $targetList[$id] = ['type' => '', 'suffix' => '', 'size' => 0, 'path' => null,];
                continue;
            }
            $targetList[$id] = [
                'type'   => $assocList[$id]['type'],
                'suffix' => $assocList[$id]['suffix'],
                'size'   => $assocList[$id]['size'],
                'path'   => self::getPath(
                    $assocList[$id]['hash'],
                    $assocList[$id]['type'],
                    $assocList[$id]['suffix'],
                    $level,
                    false,
                ),
            ];
        }
        return $targetList;
    }

    /**
     * @param string $hash 文件hash
     * @param string $type 文件类型 'audio','video','image','binary','text'
     * @param string $suffix 后缀名
     * @param string $level 预览等级 'preview','normal','raw'
     * @param bool $local 本地地址或远程地址
     * @return string
     * @todo
     */
    public static function getPath($hash, $type, $suffix, $level = '', $local = false) {
        return 'ToBeDone/ToBeDone.l:' . $level . '.h:' . $hash;
    }

    /**
     * 给文件夹自动添加封面
     * @param int $nodeId
     * @return bool
     */
    private function autoCoverFile($nodeId) {
        $node = DB::queryGetOne('select * from node where id=:id;');
        if (empty($node) || $node['is_file']) return false;
        //
        $child       = DB::query('select nd.id,ni.name from node nd left join node_info ni on nd.id=ni.id where nd.id_parent=:id and nd.is_file=1');
        $coverFileId = 0;
        if (!empty($child)) {
            usort($child, function ($a, $b) {
                return strcmp($a['name'], $b['name']);
            });
            $coverFileId = reset($child);
        }
        //没id一样更新
        DB::execute('update node_info set id_file_cover=:id_file_cover where id=:id', ['id_file_cover' => $coverFileId, 'id' => $nodeId,]);
        return true;
    }

    /**
     * 更换指定节点的文件
     * @param int $nodeId
     * @param int $fileId
     * @return bool
     */
    private function modNodeFileAssociate($nodeId, $fileId) {
        DB::execute('update assoc_node_file set status=0 where id_node=:id', ['id' => $nodeId]);
        DB::execute(
            'replace into assoc_node_file(id_node, id_file, status) value (:id_node,:id_file,1);',
            ['id_node' => $nodeId, 'id_file' => $fileId]
        );
        return true;
    }

// ----------------------------------------------------------------
// auth
// ----------------------------------------------------------------

    /**
     * @param $nodeId integer
     * @param $type string
     * @return bool
     */
    public function setAuth($nodeId, $type) {
        if (!in_array($type, ['r', 'rw', 'h',])) return false;
        DB::execute('insert into assoc_user_group_node
(id_node, id_user_group, `show`) value 
(:id_node,:id_user_group,:show);', [
            'id_node'       => $nodeId,
            'id_user_group' => $this->user['id_group'],
            'show'          => $type,
        ]);
        return true;
    }

    /** @var $authList array('node'=>'r|rw|h') */
    private $authList = [];
    /** @var $authListLoaded boolean */
    private $authListLoaded = false;

    private function getAuth() {
        if ($this->authListLoaded) return $this->authList;
        $user    = $this->user;
        $groupId = 0;
        if (!empty($user)) {
            $groupId = $user['id_group'];
        }
        $authList = DB::query(
            'select * from assoc_user_group_node where id_user_group = :id_user_group',
            ['id_user_group' => $groupId]
        );
        foreach ($authList as $auth) {
            $this->authList[$auth['id_node']] = $auth['show'];
        }
        $this->authListLoaded = true;
        return $this->authList;
    }

    /** @var $authListLoaded string 'r|rw|h' */
    private $defaultAuth = 'r';

    /**
     * 检查是否有权限
     *
     * @param $nodeTree array|integer 节点ID树
     * 正序输入（数据库顺序），内部倒序判断
     *
     * @return string 'r|rw|h'
     */
    private function checkAuth($nodeTree) {
        if (empty($nodeTree)) $nodeTree = [0];
        if (!is_array($nodeTree)) $nodeTree = [$nodeTree];
        //获取数据库
        $authList = $this->getAuth();
        //查询授权
        $auth     = $this->defaultAuth;
        $nodeTree = array_reverse($nodeTree);
        foreach ($nodeTree as $node) {
            if (empty($authList[$node])) continue;
            $auth = $authList[$node];
            break;
        }
        return $auth;
    }

    //------------------ zzz ------------------


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
        whereIn('nd.id', $totalNodeIdList)->select(
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
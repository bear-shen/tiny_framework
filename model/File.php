<?php namespace Model;

use Lib\DB;

class File {
    /**
     * @var $user array
     * ['id'=>'','id_group'=>'','name'=>'','status'=>''...]
     */
    private $user = [];

    public function __construct($user = []) {
        $this->user = $user;
    }

    /**
     * add file
     * @param $input array
     * [
     * //   'id'          =>0,
     *    'id_parent'   =>0,
     * //
     *    'hash'        =>'',
     *    'suffix'      =>'',
     *    'size'        =>0,
     *    'type'        =>'',//'audio','video','image','binary','text',
     * //
     *    'name'        =>'',
     *    'description' =>'',
     * ]
     */
    public function addFile($input) {
        $input += [
            //'id'          => 0,
            'id_parent'   => 0,
            //
            'hash'        => '',
            'suffix'      => '',
            'size'        => 0,
            'type'        => '',//'audio','video','image','binary','text',
            //
            'name'        => '',
            'description' => '',
        ];
        DB::query('insert into node(id_parent, status, sort, is_file) value ();');
        $nodeId = DB::lastInsertId();
        DB::query('insert into node_info(id, name, description) value();');
        DB::query('insert into node_tree(id, tree) value();');
        //
        DB::query('insert into file(hash, suffix, type, size) value();');
        $fileId = DB::lastInsertId();
        //
        DB::query('insert into assoc_node_file (id_node, id_file, status) value ()');
    }

    /**
     * mod file
     * @param $input array
     * [
     *    'id'          =>0,
     *    'id_parent'   =>0,
     * //
     *    'hash'        =>'',
     *    'suffix'      =>'',
     *    'size'        =>0,
     *    'type'        =>'',//'audio','video','image','binary','text',
     * //
     *    'name'        =>'',
     *    'description' =>'',
     * ]
     */
    public function modFile($input) {
        $input += [
            'id'          => 0,
            'id_parent'   => 0,
            //
            'hash'        => '',
            'suffix'      => '',
            'size'        => 0,
            'type'        => '',//'audio','video','image','binary','text',
            //
            'name'        => '',
            'description' => '',
        ];
        DB::query('select * from node where id=:id');
        DB::query('select * from node_info where id=:id');
        DB::query('update node_info set name=:name,description=:description where id=:id;');
        //
        DB::query('select * from assoc_node_file where id_node=:id');
        DB::query('select id,hash from file where id=:id;');
        DB::query('insert into file(hash, suffix, type, size) value();');
        $fileId = DB::lastInsertId();
        //
        DB::query('update assoc_node_file set status=0 where id_node=:id_node;');
        DB::query('insert into assoc_node_file (id_node, id_file, status) value ()');
    }

    /**
     * add folder
     * @param $input array
     * [
     *    'id'          =>0,
     *    'id_parent'   =>0,
     * //
     *    'name'        =>'',
     *    'description' =>'',
     * ]
     */
    public function addFolder($input) {
        $input += [
            'id'          => 0,
            'id_parent'   => 0,
            //
            'name'        => '',
            'description' => '',
        ];
        DB::query('insert into node(id_parent, status, sort, is_file) value ();');
        $nodeId = DB::lastInsertId();
        DB::query('insert into node_info(id, name, description) value();');
        DB::query('insert into node_tree(id, tree) value();');
    }

    /**
     * mod folder
     * @param $input array
     * [
     *    'id'          =>0,
     *    'id_parent'   =>0,
     * //
     *    'name'        =>'',
     *    'description' =>'',
     * ]
     */
    public function modFolder($input) {
        $input += [
            'id'          => 0,
            'id_parent'   => 0,
            //
            'name'        => '',
            'description' => '',
        ];
        DB::query('select * from node where id=:id');
        DB::query('select * from node_info where id=:id');
        DB::query('update node_info set name=:name,description=:description where id=:id;');
    }

    public function del() {
    }

    public function move() {
    }

    public function list() {
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
        DB::query('insert into assoc_user_group_node
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
}
<?php namespace Lib;

use \PDO;

/**
 * @see https://dev.mysql.com/doc/refman/5.7/en/select.html
 *
 * @method $this table($string)
 * @method $this where(...$args)
 * @method $this orWhere(...$args)
 *
 * @method $this whereRaw(string $queryString, array $binds = [])
 * @method $this orWhereRaw(string $queryString, array $binds = [])
 *
 * @method $this whereNull(string $key)
 * @method $this orWhereNull(string $key)
 * @method $this whereNotNull(string $key)
 * @method $this orWhereNotNull(string $key)
 *
 * @method $this whereIn(string $key, array $inVal)
 * @method $this orWhereIn(string $key, array $inVal)
 * @method $this whereNotIn(string $key, array $inVal)
 * @method $this orWhereNotIn(string $key, array $inVal)
 *
 * @method $this whereBetween(string $key, array $betweenVal)
 * @method $this orWhereBetween(string $key, array $betweenVal)
 * @method $this whereNotBetween(string $key, array $betweenVal)
 * @method $this orWhereNotBetween(string $key, array $betweenVal)
 *
 * @method $this order(string $key, string $sort = 'asc')
 * @method $this sort(string $key, string $sort = 'asc')
 *
 * @method $this limit(int $limit, $offset = false)
 * @method $this offset(int $offset)
 * @method $this page(int $pageNo, int $pageSize = 100)
 *
 * @method $this leftJoin($table, $left = '', $right = '', $natural = false, $outer = false)
 * @method $this rightJoin($table, $left = '', $right = '', $natural = false, $outer = false)
 * @method $this join($table, $left = '', $right = '')
 * @method $this innerJoin($table, $left = '', $right = '')
 * @method $this crossJoin($table, $left = '', $right = '')
 *
 * @method $this ignore()
 *
 * @method array selectOne(array $columns = ['*'])
 * @method array first(array $columns = ['*'])
 * @method array select(array $columns = ['*'])
 * @method integer delete()
 * @method integer insert(array $values) ex.['column1' => 'value1', 'column2' => 'value2',]
 * @method integer insertSelect($insertTable = '', $selectColumns = ['*'], $insertColumns = false)
 * @method integer update($mods = []) ex.['column1' => 'value1', 'column2' => 'value2',]
 *
 * @mixin DB
 */
class ORM extends DB {
    use FuncCallable;

    //orm结构大概这样 ?
    public $orm = [
        'table'  => '',
        'query'  => [
            [
                'type' => 'query',
                'data' => ['1', '=', '1'],
            ],
            [
                'type' => 'connect',
                'data' => 'and',
            ],
            //
            [
                'type' => 'query',
                'data' => ['a', '=', 'b'],
            ],
            [
                'type' => 'connect',
                'data' => 'and',
            ],
            [
                'type' => 'query',
                'data' => ['a', 'between', ['a', 'b']],
            ],
            [
                'type' => 'connect',
                'data' => 'and',
            ],
            [
                'type' => 'raw',
                'data' => ['a', ['a', 'b']],
            ],
            [
                'type' => 'connect',
                'data' => 'or',
            ],
            [
                'type'  => 'sub',
                'query' => [
                    [
                        'type' => 'query',
                        'data' => ['a', '=', 'b'],
                    ],
                    [
                        'type' => 'connect',
                        'data' => 'and',
                    ],
                    [
                        'type' => 'query',
                        'data' => ['a', 'between', ['a', 'b']],
                    ],
                ],
            ],
        ],
        'sort'   => [],
        'limit'  => false,
        'join'   => [],
        'ignore' => false,
        //注意顺序
        'binds'  => [
            'full'   => [],
            'where'  => [],
            'insert' => [],
            'update' => [],
        ],
    ];
    /**
     * @var array $ormQueryPos
     * 给递归用的
     */
    public $ormQueryPos = false;

    public function __construct() {
        parent::__construct();
        $this->orm         = [
            'table'  => '',
            'query'  => [],
            'sort'   => [],
            'limit'  => false,
            'join'   => [],
            'ignore' => false,
            'binds'  => [
                'full'   => [],
                'where'  => [],
                'insert' => [],
                'update' => [],
            ],
        ];
        $this->ormQueryPos =& $this->orm['query'];
    }


    //-------------------------------------------
    // orm part
    //-------------------------------------------

    protected function _table($table) {
        $this->orm['table'] = $table;
        return $this;
    }
    // -------------------------------------------------------------------

    /**
     * @param mixed ...$args
     * case size = 1
     * [['a','b'],['b','c']] cast to case size = 2 and size = 3
     * function(){}
     *
     * case size = 2
     * 'a','b'  ==> 'a=:b'
     *
     * case size = 3
     * 'a','=','b' ==> 'a=:b'
     * 'a','=','b' ==> 'a=:b'
     */
    protected function _where(...$args) {
//        var_dump(implode(':', [__FILE__, __CLASS__, __FUNCTION__,]));
        if (empty($args))
            throw new \Exception('empty query');
        //
        array_unshift($args, 'and');
        return call_user_func_array([$this, 'ormWhere'], $args);
    }

    protected function _orWhere(...$args) {
        if (empty($args))
            throw new \Exception('empty query');
        //
        array_unshift($args, 'or');
        return call_user_func_array([$this, 'ormWhere'], $args);
    }

    protected function _whereRaw($raw, $binds = []) {
        if (empty($raw))
            throw new \Exception('empty query');
        $args = ['and', $raw, 'raw', $binds];
        return call_user_func_array([$this, 'ormWhere'], $args);
    }

    protected function _orWhereRaw($raw, $binds = []) {
        if (empty($raw))
            throw new \Exception('empty query');
        $args = ['or', $raw, 'raw', $binds];
        return call_user_func_array([$this, 'ormWhere'], $args);
    }

    protected function _whereNull($param) {
        if (empty($param))
            throw new \Exception('empty query');
        $args = ['and', $param, 'is', null];
        return call_user_func_array([$this, 'ormWhere'], $args);
    }

    protected function _orWhereNull($param) {
        if (empty($param))
            throw new \Exception('empty query');
        $args = ['or', $param, 'is', null];
        return call_user_func_array([$this, 'ormWhere'], $args);
    }

    protected function _whereNotNull($param) {
        if (empty($param))
            throw new \Exception('empty query');
        $args = ['and', $param, 'is not', null];
        return call_user_func_array([$this, 'ormWhere'], $args);
    }

    protected function _orWhereNotNull($param) {
        if (empty($param))
            throw new \Exception('empty query');
        $args = ['or', $param, 'is not', null];
        return call_user_func_array([$this, 'ormWhere'], $args);
    }

    protected function _whereIn($param, $array) {
        if (empty($param))
            throw new \Exception('empty query');
        $args = ['and', $param, 'in', $array];
        return call_user_func_array([$this, 'ormWhere'], $args);
    }

    protected function _orWhereIn($param, $array) {
        if (empty($param))
            throw new \Exception('empty query');
        $args = ['or', $param, 'in', $array];
        return call_user_func_array([$this, 'ormWhere'], $args);
    }

    protected function _whereNotIn($param, $array) {
        if (empty($param))
            throw new \Exception('empty query');
        $args = ['and', $param, 'not in', $array];
        return call_user_func_array([$this, 'ormWhere'], $args);
    }

    protected function _orWhereNotIn($param, $array) {
        if (empty($param))
            throw new \Exception('empty query');
        $args = ['or', $param, 'not in', $array];
        return call_user_func_array([$this, 'ormWhere'], $args);
    }

    protected function _whereBetween($param, $array) {
        if (empty($param))
            throw new \Exception('empty query');
        $args = ['and', $param, 'between', $array];
        return call_user_func_array([$this, 'ormWhere'], $args);
    }

    protected function _orWhereBetween($param, $array) {
        if (empty($param))
            throw new \Exception('empty query');
        $args = ['or', $param, 'between', $array];
        return call_user_func_array([$this, 'ormWhere'], $args);
    }

    protected function _whereNotBetween($param, $array) {
        if (empty($param))
            throw new \Exception('empty query');
        $args = ['and', $param, 'not between', $array];
        return call_user_func_array([$this, 'ormWhere'], $args);
    }

    protected function _orWhereNotBetween($param, $array) {
        if (empty($param))
            throw new \Exception('empty query');
        $args = ['or', $param, 'not between', $array];
        return call_user_func_array([$this, 'ormWhere'], $args);
    }

    /**
     * @param string $connector
     * @param array $args
     * @return ORM
     * @throws \Exception
     * 输入一个参数时根据类型判断下面的操作
     * 一个参数为闭包或者子查询
     * 如果为数组则生成一个子查询
     * 如果为闭包则获取一个闭包
     * 这里会修改 $this->ormQueryPos 的指向，执行完成后恢复
     *
     * 两个为 a = b
     * 三个就常规查询
     */
    protected function ormWhere($connector = 'and', ...$args) {
        if (!empty($this->ormQueryPos) && end($this->ormQueryPos)['type'] !== 'connect') {
            $this->ormQueryPos[] = [
                'type' => 'connect',
                'data' => $connector,
            ];
        }
//        var_dump($args);
//        var_dump($args);
        switch (sizeof($args)) {
            case 1:
//                var_dump($args[0]);
//                var_dump(gettype($args[0]));
//                var_dump($args[0] instanceof \Closure);
                switch (gettype($args[0])) {
                    case 'array':
                        $this->ormQueryPos[] = [
                            'type'  => 'sub',
                            'query' => []
                        ];
                        $before              =& $this->ormQueryPos;
                        $this->ormQueryPos   =& $this->ormQueryPos[sizeof($this->ormQueryPos) - 1]['query'];
                        //-------------------------------------------------------
                        foreach ($args[0] as $sub) {
                            array_unshift($sub, 'and');
                            call_user_func_array([$this, 'ormWhere'], $sub);
                        }
                        //-------------------------------------------------------
                        $this->ormQueryPos =& $before;
                        break;
                    case 'object':
                        if (!($args[0] instanceof \Closure))
                            throw new \Exception('unsupported ORM where param');
                        $this->ormQueryPos[] = [
                            'type'  => 'sub',
                            'query' => []
                        ];
                        $before              =& $this->ormQueryPos;
                        $this->ormQueryPos   =& $this->ormQueryPos[sizeof($this->ormQueryPos) - 1]['query'];
                        //-------------------------------------------------------
                        $args[0]($this);
                        //-------------------------------------------------------
                        $this->ormQueryPos =& $before;
                        break;
                    default:
                        throw new \Exception('unsupported ORM where method');
                }
                break;
            case 2:
                $this->ormQueryPos[] = [
                    'type' => 'query',
                    'data' => [$args[0], '=', $args[1]],
                ];
                break;
            case 3:
                switch ($args[1]) {
                    case 'raw':
                        $this->ormQueryPos[] = [
                            'type' => 'raw',
                            'data' => [$args[0], $args[2]],
                        ];
                        break;
                    case 'like':
                    case 'is':
                    case 'is not':
                    case 'in':
                    case 'not in':
                    case 'between':
                    case 'not between':
                        $this->ormQueryPos[] = [
                            'type' => 'query',
                            'data' => [$args[0], $args[1], $args[2]],
                        ];
                        break;
                    default:
                        if (!in_array($args[1], ['=', '<', '>', '!=', '<>', '>=', '<=', '<=>',]))
                            throw new \Exception('unsupported ORM where operator');
                        $this->ormQueryPos[] = [
                            'type' => 'query',
                            'data' => [$args[0], $args[1], $args[2]],
                        ];
                        break;
                }
                /*$this->ormQueryPos[] = [
                    'type' => 'query',
                    'data' => [$args[0], $args[1], $args[2]],
                ];*/
                break;
            default:
                throw new \Exception('unsupported ORM where arguments');
        }
        return $this;
    }

    protected function ormMakeWhere($query) {
        $queryArr = [];
        foreach ($query as $sub) {
//            var_dump($sub);
            $subStr = '';
            switch ($sub['type']) {
                case 'query':
                    switch ($sub['data'][1]) {
                        case 'is':
                        case 'is not':
                        default:
                            $subStr                       =
                                $sub['data'][0]
                                . (
                                isset($sub['data'][1]) ? (' ' . $sub['data'][1] . ' ') : ''
                                )
                                . ' ? ';
                            $this->orm['binds']['full'][] = $sub['data'][2];
                            break;
                        case 'between':
                        case 'not between':
                            $subStr                       =
                                $sub['data'][0]
                                . ' ' . $sub['data'][1] . ' '
                                . '? and ?';
                            $this->orm['binds']['full'][] = $sub['data'][2][0];
                            $this->orm['binds']['full'][] = $sub['data'][2][1];
                            break;
                        case 'in':
                        case 'not in':
                            $subArr = [];
                            foreach ($sub['data'][2] as $subItem) {
                                $subArr[]                     = '?';
                                $this->orm['binds']['full'][] = $subItem;
                            }
                            $subStr =
                                $sub['data'][0]
                                . ' ' . $sub['data'][1] . ' '
                                . '(' . implode(',', $subArr) . ')';
                            break;
                    }
                    break;
                case 'connect':
                    $subStr = $sub['data'];
                    break;
                case 'raw':
                    $subStr = $sub['data'][0];
//                    var_dump($sub);
                    foreach ($sub['data'][1] as $val) {
                        $this->orm['binds']['full'][] = $val;
                    }
                    break;
                case 'sub':
                    if (!empty($sub['query'])) {
                        $subStr = '( ' . $this->ormMakeWhere($sub['query']) . ' )';
                    }
                    break;
                default:
                    throw new \Exception('unsupported ORM query method');
                    break;
            }
            $queryArr[] = $subStr;
        }
        //过滤掉前置或者后置的连接符，主要是子查询这边可能会产生问题
        //@todo 连接最好改到和ormMake函数一起去做，不过目前屏蔽一下and和or也就完事了
        $queryArr = array_values(array_filter($queryArr));
        if (!empty($queryArr)) {
            if (in_array($queryArr[sizeof($queryArr) - 1], ['and', 'or',])) {
                unset($queryArr[sizeof($queryArr) - 1]);
            }
            if (in_array($queryArr[0], ['and', 'or',])) {
                unset($queryArr[0]);
            }
        }
        $queryArr = array_values($queryArr);
        return implode(" ", $queryArr);
    }

    // -------------------------------------------------------------------

    protected function _order($key, $sort = 'asc') {
        $this->orm['sort'][] = [$key, $sort];
        return $this;
    }

    protected function _sort($key, $sort = 'asc') {
        return $this->_order($key, $sort);
    }

    protected function ormMakeSort($sortArr) {
        $queryArr = [];
        foreach ($sortArr as $sub) {
//            var_dump($sub);
            $sc         = (isset($sub[1]) && $sub[1] == 'desc') ? 'desc' : 'asc';
            $queryArr[] = $sub[0] . ' ' . $sc;
        }
        return implode(',', $queryArr);
    }

    // -------------------------------------------------------------------

    protected function _limit($limit, $offset = false) {
        $offset             = $this->orm['limit'] && $offset === false ? $this->orm['limit'][1] : $offset;
        $this->orm['limit'] = [$limit, $offset];
        return $this;
    }

    protected function _offset($offset) {
        $limit              = $this->orm['limit'] ? $this->orm['limit'][0] : 0;
        $this->orm['limit'] = [$limit, $offset];
        return $this;
    }

    protected function _page($pageNo, $pageSize = 100) {
        $this->orm['limit'] = [$pageSize, $pageSize * (max(1, $pageNo) - 1)];
        return $this;
    }

    protected function ormMakeLimit($limitData) {
        $str = '';
        if (!$limitData) return $str;
        list($limit, $offset) = $limitData;
        $str = "$limit offset $offset";
        return $str;
    }

    // -------------------------------------------------------------------
    //@see https://dev.mysql.com/doc/refman/5.7/en/join.html
    /**
     */
    protected function _leftJoin($table, $left = '', $right = '', $natural = false, $outer = false) {
        $this->orm['join'][] = [
            'type'    => 'left',
            'table'   => $table,
            'left'    => $left,
            'right'   => $right,
            'natural' => $natural,
            'outer'   => $outer,
        ];
        return $this;
    }

    protected function _rightJoin($table, $left = '', $right = '', $natural = false, $outer = false) {
        $this->orm['join'][] = [
            'type'    => 'right',
            'table'   => $table,
            'left'    => $left,
            'right'   => $right,
            'natural' => $natural,
            'outer'   => $outer,
        ];
        return $this;
    }

    protected function _join($table, $left = '', $right = '') {
        $this->orm['join'][] = [
            'type'  => 'join',
            'table' => $table,
            'left'  => $left,
            'right' => $right,
        ];
        return $this;
    }

    protected function _innerJoin($table, $left = '', $right = '') {
        return $this->_join($table, $left, $right);
    }

    protected function _crossJoin($table, $left = '', $right = '') {
        $this->orm['join'][] = [
            'type'  => 'cross join',
            'table' => $table,
            'left'  => $left,
            'right' => $right,
        ];
        return $this;
    }

    protected function ormMakeJoin($joins) {
        $joinArr = [];
        foreach ($joins as $join) {
            $on = '';
            if (!empty($join['left']) || !empty($join['right'])) {
                if (empty($join['right'])) $join['right'] = $join['left'];
                if (empty($join['left'])) $join['left'] = $join['right'];
                $on = 'on ' . $join['left'] . ' = ' . $join['right'];
            }
            switch ($join['type']) {
                case 'left':
                case 'right':
                    $joinArr[] = ($join['natural'] ? 'natural ' : '')
                                 . $join['type'] . ' join '
                                 . ($join['outer'] ? 'outer ' : '')
                                 . $join['table']
                                 . ' ' . $on;
                    break;
                default:
                    $joinArr[] = $join['type']
                                 . ' ' . $join['table']
                                 . ' ' . $on;
                    break;
            }
        }
        return implode(' ', $joinArr);
    }

    // -------------------------------------------------------------------


    protected function _first($columns = ['*']) {
        $data = $this->_select($columns);
        if (!empty($data)) return $data[0];
        return null;
    }

    protected function _selectOne($columns = ['*']) {
        $data = $this->_select($columns);
        if (!empty($data)) return $data[0];
        return null;
    }

    protected function _select($columns = ['*']) {
        $colStr = [];
        foreach ($columns as $column) {
            $colStr[] = "$column";
        }
        $colStr = implode(',', $colStr);
        $table  = $this->getOrmTable($this->orm);
        $where  = $this->ormMakeWhere($this->orm['query']);
        if (!empty($where)) {
            $where = "where $where";
        }
        $orderBy = $this->ormMakeSort($this->orm['sort']);
        if (!empty($orderBy)) {
            $orderBy = "order by $orderBy";
        }
        $limit = $this->ormMakeLimit($this->orm['limit']);
        if (!empty($limit)) {
            $limit = "limit $limit";
        }
        $join = $this->ormMakeJoin($this->orm['join']);
        $str  = "select $colStr from " . implode(' ', [$table, $join, $where, $orderBy, $limit]) . ';';
//        var_dump($str);
//        var_dump(static::class);
//        var_dump(self::class);
//        var_dump(parent::class);
        return $this->_query($str, $this->orm['binds']['full']);
    }

    // -------------------------------------------------------------------

    protected function _ignore() {
        $this->orm['ignore'] = true;
        return $this;
    }

    protected function _delete() {
        $table  = $this->getOrmTable($this->orm);
        $ignore = $this->orm['ignore'] ? ' ignore ' : ' ';
        $where  = $this->ormMakeWhere($this->orm['query']);
        if (!empty($where)) {
            $where = "where $where";
        }
        $orderBy = $this->ormMakeSort($this->orm['sort']);
        if (!empty($orderBy)) {
            $orderBy = "order by $orderBy";
        }
        $limit = $this->ormMakeLimit($this->orm['limit']);
        if (!empty($limit)) {
            $limit = "limit $limit";
        }
        $str = "delete{$ignore}from " . implode(' ', [$table, $where, $orderBy, $limit]) . ';';
//        var_dump($str);
        return $this->_execute($str, $this->orm['binds']['full']);
    }

    protected function _insert($data = []) {
        $table  = $this->getOrmTable($this->orm);
        $ignore = $this->orm['ignore'] ? ' ignore ' : ' ';
        $str    = "insert{$ignore}into $table (:k) values (:v)";
        return $this->_execute($str, [], $data);
    }

    protected function _insertSelect($insertTable = '', $selectColumns = ['*'], $insertColumns = false) {
        if (!$insertColumns) $insertColumns = $selectColumns;

        // ---------- select part ----------
        $colStr = implode(',', $selectColumns);
        $table  = $this->getOrmTable($this->orm);
        $where  = $this->ormMakeWhere($this->orm['query']);
        if (!empty($where)) {
            $where = "where $where";
        }
        $orderBy = $this->ormMakeSort($this->orm['sort']);
        if (!empty($orderBy)) {
            $orderBy = "order by $orderBy";
        }
        $limit = $this->ormMakeLimit($this->orm['limit']);
        if (!empty($limit)) {
            $limit = "limit $limit";
        }
        $join = $this->ormMakeJoin($this->orm['join']);
        $str  = "select $colStr from " . implode(' ', [$table, $join, $where, $orderBy, $limit]);
        // ---------- insert part ----------
        $ignore = $this->orm['ignore'] ? ' ignore ' : ' ';
        $colStr = implode(',', $selectColumns);
        $table  = $insertTable;

        $str = "insert{$ignore}into $table $colStr $str;";
        return $this->_execute($str);
    }

    protected function _update($mods = []) {
        $ignore = $this->orm['ignore'] ? ' ignore ' : ' ';
        //
        $table  = $this->getOrmTable($this->orm);
        $setStr = [];
        foreach ($mods as $key => $val) {
            $setStr []                    = "$key = ?";
            $this->orm['binds']['full'][] = $val;
        }
        $where = $this->ormMakeWhere($this->orm['query']);
        if (!empty($where)) {
            $where = "where $where";
        }
        $orderBy = $this->ormMakeSort($this->orm['sort']);
        if (!empty($orderBy)) {
            $orderBy = "order by $orderBy";
        }
        $limit = $this->ormMakeLimit($this->orm['limit']);
        if (!empty($limit)) {
            $limit = "limit $limit";
        }
        $setStr = implode(' , ', $setStr);
        $str    = "update{$ignore}$table set $setStr " . implode(' ', [$where, $orderBy, $limit]);
//        var_dump($str);
//        exit();
        return $this->_execute($str, $this->orm['binds']['full']);
    }

    protected function getOrmTable($orm) {
        if (empty($orm['table'])) throw new \Exception('query table not defined');
        return $orm['table'];
    }

    // -------------------------------------------------------------------


    public static function ormQuote($data) {
        $type = PDO::PARAM_STR;
        switch (gettype($data)) {
            case 'boolean':
                $type = PDO::PARAM_BOOL;
                $data = $data ? 'true' : 'false';
                break;
            case 'integer':
                $type = PDO::PARAM_INT;
                //$data=$data;
                break;
            case 'double':
            case 'string':
                $type = PDO::PARAM_STR;
                $data = self::$pdo->quote($data, $type);
                break;
            case 'NULL':
                $type = PDO::PARAM_NULL;
                $data = 'null';
                break;
            case 'array':
            case 'object':
            case 'resource':
            default:
                throw new \Exception('unsupported quote param');
                break;
        }
        return $data;
    }

}


/**
 * --------------------------------------------------------------------
 * DELETE [LOW_PRIORITY] [QUICK] [IGNORE] FROM tbl_name
 * [PARTITION (partition_name [, partition_name] ...)]
 * [WHERE where_condition]
 * [ORDER BY ...]
 * [LIMIT row_count]
 *
 * --------------------------------------------------------------------
 * DO expr [, expr] ...
 *
 * --------------------------------------------------------------------
 * INSERT [LOW_PRIORITY | DELAYED | HIGH_PRIORITY] [IGNORE]
 * [INTO] tbl_name
 * [PARTITION (partition_name [, partition_name] ...)]
 * [(col_name [, col_name] ...)]
 * {VALUES | VALUE} (value_list) [, (value_list)] ...
 * [ON DUPLICATE KEY UPDATE assignment_list]
 *
 * INSERT [LOW_PRIORITY | DELAYED | HIGH_PRIORITY] [IGNORE]
 * [INTO] tbl_name
 * [PARTITION (partition_name [, partition_name] ...)]
 * SET assignment_list
 * [ON DUPLICATE KEY UPDATE assignment_list]
 *
 * INSERT [LOW_PRIORITY | HIGH_PRIORITY] [IGNORE]
 * [INTO] tbl_name
 * [PARTITION (partition_name [, partition_name] ...)]
 * [(col_name [, col_name] ...)]
 * SELECT ...
 * [ON DUPLICATE KEY UPDATE assignment_list]
 *
 * value:
 * {expr | DEFAULT}
 *
 * value_list:
 * value [, value] ...
 *
 * assignment:
 * col_name = value
 *
 * assignment_list:
 * assignment [, assignment] ...
 *
 * --------------------------------------------------------------------
 * REPLACE [LOW_PRIORITY | DELAYED]
 * [INTO] tbl_name
 * [PARTITION (partition_name [, partition_name] ...)]
 * [(col_name [, col_name] ...)]
 * {VALUES | VALUE} (value_list) [, (value_list)] ...
 *
 * REPLACE [LOW_PRIORITY | DELAYED]
 * [INTO] tbl_name
 * [PARTITION (partition_name [, partition_name] ...)]
 * SET assignment_list
 *
 * REPLACE [LOW_PRIORITY | DELAYED]
 * [INTO] tbl_name
 * [PARTITION (partition_name [, partition_name] ...)]
 * [(col_name [, col_name] ...)]
 * SELECT ...
 *
 * value:
 * {expr | DEFAULT}
 *
 * value_list:
 * value [, value] ...
 *
 * assignment:
 * col_name = value
 *
 * assignment_list:
 * assignment [, assignment] ...
 *
 * --------------------------------------------------------------------
 * SELECT
 * [ALL | DISTINCT | DISTINCTROW ]
 * [HIGH_PRIORITY]
 * [STRAIGHT_JOIN]
 * [SQL_SMALL_RESULT] [SQL_BIG_RESULT] [SQL_BUFFER_RESULT]
 * [SQL_CACHE | SQL_NO_CACHE] [SQL_CALC_FOUND_ROWS]
 * select_expr [, select_expr] ...
 * [into_option]
 * [FROM table_references
 * [PARTITION partition_list]]
 * [WHERE where_condition]
 * [GROUP BY {col_name | expr | position}
 * [ASC | DESC], ... [WITH ROLLUP]]
 * [HAVING where_condition]
 * [ORDER BY {col_name | expr | position}
 * [ASC | DESC], ...]
 * [LIMIT {[offset,] row_count | row_count OFFSET offset}]
 * [PROCEDURE procedure_name(argument_list)]
 * [into_option]
 * [FOR UPDATE | LOCK IN SHARE MODE]
 *
 * into_option: {
 * INTO OUTFILE 'file_name'
 * [CHARACTER SET charset_name]
 * export_options
 * | INTO DUMPFILE 'file_name'
 * | INTO var_name [, var_name] ...
 * }
 *
 * --------------------------------------------------------------------
 * UPDATE [LOW_PRIORITY] [IGNORE] table_reference
 * SET assignment_list
 * [WHERE where_condition]
 * [ORDER BY ...]
 * [LIMIT row_count]
 *
 * value:
 * {expr | DEFAULT}
 *
 * assignment:
 * col_name = value
 *
 * assignment_list:
 * assignment [, assignment] ...
 *
 * UPDATE [LOW_PRIORITY] [IGNORE] table_references
 * SET assignment_list
 * [WHERE where_condition]
 *
 * --------------------------------------------------------------------
 */
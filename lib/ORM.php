<?php namespace Lib;

use \PDO;

/**
 * @todo where 和 sort 的左边还有 select 都没有防注入。。。
 * @todo ormMakeWhere / ormMakeSort / select
 * @todo 这个还是要加的吧。。。不过没想好怎么做
 * @todo 显然用原生绑定可以减少问题。。。但是总之也麻烦。。。
 *
 * @see https://dev.mysql.com/doc/refman/5.7/en/select.html
 *
 * @see ORM::_table()
 * @method ORM table($string)
 * @see ORM::_where()
 * @method ORM where(...$args)
 * @method ORM orWhere(...$args)
 *
 * @method ORM whereRaw(string $queryString)
 * @method ORM orWhereRaw(string $queryString)
 *
 * @method ORM whereNull(string $key)
 * @method ORM orWhereNull(string $key)
 * @method ORM whereNotNull(string $key)
 * @method ORM orWhereNotNull(string $key)
 *
 * @method ORM whereIn(string $key, array $inVal)
 * @method ORM orWhereIn(string $key, array $inVal)
 * @method ORM whereNotIn(string $key, array $inVal)
 * @method ORM orWhereNotIn(string $key, array $inVal)
 *
 * @method ORM whereBetween(string $key, array $betweenVal)
 * @method ORM orWhereBetween(string $key, array $betweenVal)
 * @method ORM whereNotBetween(string $key, array $betweenVal)
 * @method ORM orWhereNotBetween(string $key, array $betweenVal)
 *
 * @method ORM order(string $key, string $sort = 'asc')
 * @method ORM sort(string $key, string $sort = 'asc')
 *
 * @method ORM limit(int $limit, $offset = false)
 * @method ORM offset(int $offset)
 *
 * @method ORM leftJoin($table, $left = '', $right = '', $natural = false, $outer = false)
 * @method ORM rightJoin($table, $left = '', $right = '', $natural = false, $outer = false)
 * @method ORM join($table, $left = '', $right = '')
 * @method ORM innerJoin($table, $left = '', $right = '')
 * @method ORM crossJoin($table, $left = '', $right = '')
 *
 * @method ORM ignore()
 *
 * @method array selectOne(array $columns = ['*'])
 * @method array first(array $columns = ['*'])
 * @method array select(array $columns = ['*'])
 * @method array delete() @debug
 * @method array insert(array $values) @debug ex.['column1' => 'value1', 'column2' => 'value2',]
 * @method array insertSelect($insertTable = '', $selectColumns = ['*'], $insertColumns = false) @debug
 * @method array update($mods = []) @debug ex.['column1' => 'value1', 'column2' => 'value2',]
 *
 */
class ORM extends DB {
    use FuncCallable;

    //orm结构大概这样 ?
    public static $orm = [
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
                'data' => 'a',
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
    ];
    /** @var array $ormQueryPos */
    public $ormQueryPos = false;

    public function __construct() {
        parent::__construct();
        self::$orm         = [
            'table'  => '',
            'query'  => [],
            'sort'   => [],
            'limit'  => false,
            'join'   => [],
            'ignore' => false,
        ];
        $this->ormQueryPos =& self::$orm['query'];
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
    private function _where(...$args) {
        if (empty($args))
            throw new \Exception('empty query');
        //
        array_unshift($args, 'and');
        return call_user_func_array([$this, 'ormWhere'], $args);
    }

    private function _orWhere(...$args) {
        if (empty($args))
            throw new \Exception('empty query');
        //
        array_unshift($args, 'or');
        return call_user_func_array([$this, 'ormWhere'], $args);
    }

    private function _whereRaw($raw) {
        if (empty($raw))
            throw new \Exception('empty query');
        $args = ['and', $raw, 'raw', null];
        return call_user_func_array([$this, 'ormWhere'], $args);
    }

    private function _orWhereRaw($raw) {
        if (empty($raw))
            throw new \Exception('empty query');
        $args = ['or', $raw, 'raw', null];
        return call_user_func_array([$this, 'ormWhere'], $args);
    }

    private function _whereNull($param) {
        if (empty($param))
            throw new \Exception('empty query');
        $args = ['and', $param, 'is', null];
        return call_user_func_array([$this, 'ormWhere'], $args);
    }

    private function _orWhereNull($param) {
        if (empty($param))
            throw new \Exception('empty query');
        $args = ['or', $param, 'is', null];
        return call_user_func_array([$this, 'ormWhere'], $args);
    }

    private function _whereNotNull($param) {
        if (empty($param))
            throw new \Exception('empty query');
        $args = ['and', $param, 'is not', null];
        return call_user_func_array([$this, 'ormWhere'], $args);
    }

    private function _orWhereNotNull($param) {
        if (empty($param))
            throw new \Exception('empty query');
        $args = ['or', $param, 'is not', null];
        return call_user_func_array([$this, 'ormWhere'], $args);
    }

    private function _whereIn($param, $array) {
        if (empty($param))
            throw new \Exception('empty query');
        $args = ['and', $param, 'in', $array];
        return call_user_func_array([$this, 'ormWhere'], $args);
    }

    private function _orWhereIn($param, $array) {
        if (empty($param))
            throw new \Exception('empty query');
        $args = ['or', $param, 'in', $array];
        return call_user_func_array([$this, 'ormWhere'], $args);
    }

    private function _whereNotIn($param, $array) {
        if (empty($param))
            throw new \Exception('empty query');
        $args = ['and', $param, 'not in', $array];
        return call_user_func_array([$this, 'ormWhere'], $args);
    }

    private function _orWhereNotIn($param, $array) {
        if (empty($param))
            throw new \Exception('empty query');
        $args = ['or', $param, 'not in', $array];
        return call_user_func_array([$this, 'ormWhere'], $args);
    }

    private function _whereBetween($param, $array) {
        if (empty($param))
            throw new \Exception('empty query');
        $args = ['and', $param, 'between', $array];
        return call_user_func_array([$this, 'ormWhere'], $args);
    }

    private function _orWhereBetween($param, $array) {
        if (empty($param))
            throw new \Exception('empty query');
        $args = ['or', $param, 'between', $array];
        return call_user_func_array([$this, 'ormWhere'], $args);
    }

    private function _whereNotBetween($param, $array) {
        if (empty($param))
            throw new \Exception('empty query');
        $args = ['and', $param, 'not between', $array];
        return call_user_func_array([$this, 'ormWhere'], $args);
    }

    private function _orWhereNotBetween($param, $array) {
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
    private function ormWhere($connector = 'and', ...$args) {
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
                            'data' => $args[0],
                        ];
                        break;
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

    private function ormMakeWhere($query) {
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
                            $subStr =
                                $sub['data'][0]
                                . (
                                isset($sub['data'][1]) ? (' ' . $sub['data'][1] . ' ') : ''
                                )
                                . $this->ormQuote($sub['data'][2]);
                            break;
                        case 'between':
                        case 'not between':
                            $subStr =
                                $sub['data'][0]
                                . ' ' . $sub['data'][1] . ' '
                                . $this->ormQuote($sub['data'][2][0])
                                . ' and '
                                . $this->ormQuote($sub['data'][2][1]);
                            break;
                        case 'in':
                        case 'not in':
                            $subArr = [];
                            foreach ($sub['data'][2] as $subItem) {
                                $subArr[] = $this->ormQuote($subItem);
                            }
                            $subStr =
                                $sub['data'][0]
                                . ' ' . $sub['data'][1] . ' '
                                . '(' . implode(',', $subArr) . ')';
                            break;
                    }
                    break;
                case 'connect':
                case 'raw':
                    $subStr = $sub['data'];
                    break;
                case 'sub':
                    $subStr = '( ' . $this->ormMakeWhere($sub['query']) . ' )';
                    break;
                default:
                    throw new \Exception('unsupported ORM query method');
                    break;
            }
//            var_dump($subStr);
            $queryArr[] = $subStr;
        }
        return implode(" ", $queryArr);
    }

    // -------------------------------------------------------------------

    private function _order($key, $sort = 'asc') {
        self::$orm['sort'][] = [$key, $sort];
        return $this;
    }

    private function _sort($key, $sort = 'asc') {
        return $this->_order($key, $sort);
    }

    private function ormMakeSort($sortArr) {
        $queryArr = [];
        foreach ($sortArr as $sub) {
//            var_dump($sub);
            $sc         = (isset($sub[1]) && $sub[1] == 'desc') ? 'desc' : 'asc';
            $queryArr[] = $sub[0] . ' ' . $sc;
        }
        return implode(',', $queryArr);
    }

    // -------------------------------------------------------------------

    private function _limit($limit, $offset = false) {
        $offset             = self::$orm['limit'] && $offset === false ? self::$orm['limit'][1] : $offset;
        self::$orm['limit'] = [$limit, $offset];
        return $this;
    }

    private function _offset($offset) {
        $limit              = self::$orm['limit'] ? self::$orm['limit'][0] : 0;
        self::$orm['limit'] = [$limit, $offset];
        return $this;
    }

    private function ormMakeLimit($limitData) {
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
    private function _leftJoin($table, $left = '', $right = '', $natural = false, $outer = false) {
        self::$orm['join'][] = [
            'type'    => 'left',
            'table'   => $table,
            'left'    => $left,
            'right'   => $right,
            'natural' => $natural,
            'outer'   => $outer,
        ];
        return $this;
    }

    private function _rightJoin($table, $left = '', $right = '', $natural = false, $outer = false) {
        self::$orm['join'][] = [
            'type'    => 'right',
            'table'   => $table,
            'left'    => $left,
            'right'   => $right,
            'natural' => $natural,
            'outer'   => $outer,
        ];
        return $this;
    }

    private function _join($table, $left = '', $right = '') {
        self::$orm['join'][] = [
            'type'  => 'join',
            'table' => $table,
            'left'  => $left,
            'right' => $right,
        ];
        return $this;
    }

    private function _innerJoin($table, $left = '', $right = '') {
        return $this->_join($table, $left, $right);
    }

    private function _crossJoin($table, $left = '', $right = '') {
        self::$orm['join'][] = [
            'type'  => 'cross join',
            'table' => $table,
            'left'  => $left,
            'right' => $right,
        ];
        return $this;
    }

    private function ormMakeJoin($joins) {
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


    private function _first($columns = ['*']) {
        $data = $this->_select($columns);
        if (!empty($data)) return $data[0];
        return null;
    }

    private function _selectOne($columns = ['*']) {
        $data = $this->_select($columns);
        if (!empty($data)) return $data[0];
        return null;
    }

    private function _select($columns = ['*']) {
        $colStr = [];
        foreach ($columns as $column) {
            $colStr[] = $column;
        }
        $colStr = implode(',', $colStr);
        $table  = $this->getOrmTable(self::$orm);
        $where  = $this->ormMakeWhere(self::$orm['query']);
        if (!empty($where)) {
            $where = "where $where";
        }
        $orderBy = $this->ormMakeSort(self::$orm['sort']);
        if (!empty($orderBy)) {
            $orderBy = "order by $orderBy";
        }
        $limit = $this->ormMakeLimit(self::$orm['limit']);
        if (!empty($limit)) {
            $limit = "limit $limit";
        }
        $join = $this->ormMakeJoin(self::$orm['join']);
        $str  = "select $colStr from " . implode(' ', [$table, $join, $where, $orderBy, $limit]) . ';';
        return $this->_query($str);
    }

    // -------------------------------------------------------------------

    private function _ignore() {
        self::$orm['ignore'] = true;
        return $this;
    }

    private function _delete() {
        $table  = $this->getOrmTable(self::$orm);
        $ignore = self::$orm['ignore'] ? ' ignore ' : ' ';
        $where  = $this->ormMakeWhere(self::$orm['query']);
        if (!empty($where)) {
            $where = "where $where";
        }
        $orderBy = $this->ormMakeSort(self::$orm['sort']);
        if (!empty($orderBy)) {
            $orderBy = "order by $orderBy";
        }
        $limit = $this->ormMakeLimit(self::$orm['limit']);
        if (!empty($limit)) {
            $limit = "limit $limit";
        }
        $str = "delete{$ignore}from " . implode(' ', [$table, $where, $orderBy, $limit]) . ';';
//        var_dump($str);
        return $this->_execute($str);
    }

    private function _insert($data = []) {
        $table  = $this->getOrmTable(self::$orm);
        $ignore = self::$orm['ignore'] ? ' ignore ' : ' ';
        $str    = "insert{$ignore}into $table (:k) values (:v)";
        return $this->_execute($str, [], $data);
    }

    private function _insertSelect($insertTable = '', $selectColumns = ['*'], $insertColumns = false) {
        if (!$insertColumns) $insertColumns = $selectColumns;

        // ---------- select part ----------
        $colStr = implode(',', $selectColumns);
        $table  = $this->getOrmTable(self::$orm);
        $where  = $this->ormMakeWhere(self::$orm['query']);
        if (!empty($where)) {
            $where = "where $where";
        }
        $orderBy = $this->ormMakeSort(self::$orm['sort']);
        if (!empty($orderBy)) {
            $orderBy = "order by $orderBy";
        }
        $limit = $this->ormMakeLimit(self::$orm['limit']);
        if (!empty($limit)) {
            $limit = "limit $limit";
        }
        $join = $this->ormMakeJoin(self::$orm['join']);
        $str  = "select $colStr from " . implode(' ', [$table, $join, $where, $orderBy, $limit]);
        // ---------- insert part ----------
        $ignore = self::$orm['ignore'] ? ' ignore ' : ' ';
        $colStr = implode(',', $selectColumns);
        $table  = $insertTable;

        $str = "insert{$ignore}into $table $colStr $str;";
        return $this->_execute($str);
    }

    private function _update($mods = []) {
        $ignore = self::$orm['ignore'] ? ' ignore ' : ' ';
        //
        $table = $this->getOrmTable(self::$orm);
        $where = $this->ormMakeWhere(self::$orm['query']);
        if (!empty($where)) {
            $where = "where $where";
        }
        $orderBy = $this->ormMakeSort(self::$orm['sort']);
        if (!empty($orderBy)) {
            $orderBy = "order by $orderBy";
        }
        $limit = $this->ormMakeLimit(self::$orm['limit']);
        if (!empty($limit)) {
            $limit = "limit $limit";
        }
        $setStr = [];
        foreach ($mods as $key => $val) {
            $setStr [] = "$key = $val";
        }
        $setStr = implode(' , ', $setStr);
        $str    = "update{$ignore}$table set $setStr " . implode(' ', [$where, $orderBy, $limit]);
        return $this->_execute($str);
    }

    private function getOrmTable($orm) {
        if (empty($orm['table'])) throw new \Exception('query table not defined');
        return $orm['table'];
    }

    // -------------------------------------------------------------------


    private function ormQuote($data) {
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
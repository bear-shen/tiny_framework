<?php namespace Lib;

use \PDO;

/**
 *
 * @see ORM::_table()
 * @method ORM table($string)
 * @see ORM::_where()
 * @method ORM where(...$args)
 * @method ORM orWhere(...$args)
 *
 * @todo @method ORM whereRaw(string $key, array $inVal)
 * @todo @method ORM orWhereRaw(string $key, array $inVal)
 *
 * @todo @method ORM whereNull(string $key, array $inVal)
 * @todo @method ORM orWhereNull(string $key, array $inVal)
 * @todo @method ORM whereNotNull(string $key, array $inVal)
 * @todo @method ORM orWhereNotNull(string $key, array $inVal)
 *
 * @todo @method ORM whereIn(string $key, array $inVal)
 * @todo @method ORM orWhereIn(string $key, array $inVal)
 * @todo @method ORM whereNotIn(string $key, array $inVal)
 * @todo @method ORM orWhereNotIn(string $key, array $inVal)
 *
 * @todo @method ORM whereBetween(string $key, array $betweenVal)
 * @todo @method ORM orWhereBetween(string $key, array $betweenVal)
 * @todo @method ORM whereNotBetween(string $key, array $betweenVal)
 * @todo @method ORM orWhereNotBetween(string $key, array $betweenVal)
 *
 * @todo @method ORM order(string $key, array $betweenVal)
 * @todo @method ORM sort(string $key, array $betweenVal)
 *
 * @todo @method array selectOne(array $columns = ['*'])
 * @todo @method array first(array $columns = ['*'])
 * @todo @method array select(array $columns = ['*'])
 * @todo @method array delete(array $columns = ['*'])
 * @todo @method array insert(array $values) ex.['column1' => 'value1', 'column2' => 'value2',]
 * @todo @method array update(array $keyValue) ex.['column1' => 'value1', 'column2' => 'value2',]
 *
 */
class ORM extends DB {
    use FuncCallable;

    //orm结构大概这样 ?
    public static $orm = [
        'table' => '',
        'query' => [
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
        'sort'  => [],
    ];
    /** @var array $ormQueryPos */
    public $ormQueryPos = false;

    public function __construct() {
        parent::__construct();
        self::$orm         = [
            'table' => '',
            'query' => [],
        ];
        $this->ormQueryPos =& self::$orm['query'];
    }

    //-------------------------------------------
    // orm part
    //-------------------------------------------

    private function _table($table) {
        self::$orm['table'] = $table;
        return $this;
    }

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
        array_unshift($args, 'and');
        /*if (gettype($args[0]) !== 'object' || get_class($args[0]) !== __CLASS__) {
            $pos =& self::$orm['query'];
            array_unshift($args, $pos);
        }*/
//        var_dump($args);
        return call_user_func_array([$this, 'ormWhere'], $args);
    }

    private function _orWhere(...$args) {
        if (empty($args))
            throw new \Exception('empty query');
        array_unshift($args, 'or');
        /*if (gettype($args[0]) !== 'object' || get_class($args[0]) !== __CLASS__) {
            $pos =& self::$orm['query'];
            array_unshift($args, $pos);
        }*/
//        var_dump($args);
        return call_user_func_array([$this, 'ormWhere'], $args);
    }

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
                            throw new \Exception('unsupported ORM closure');
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
                $this->ormQueryPos[] = [
                    'type' => 'query',
                    'data' => [$args[0], $args[1], $args[2]],
                ];
                break;
            default:
                throw new \Exception('unsupported ORM where arguments');
        }
        return $this;
    }

    private function ormMakeWhere($query) {
        $queryArr = [];
        foreach ($query as $sub) {
            $subStr = '';
            switch ($sub['type']) {
                case 'query':
                    switch ($sub['data'][1]) {
                        default:
                            $subStr =
                                $sub['data'][0]
                                . ' ' . $sub['data'][1] . ' '
                                . $this->ormQuote($sub['data'][2]);
                            break;
                        case 'between':
                            $subStr =
                                $sub['data'][0]
                                . ' ' . $sub['data'][1] . ' '
                                . $this->ormQuote($sub['data'][2][0])
                                . ' and '
                                . $this->ormQuote($sub['data'][2][1]);
                            break;
                        case 'in':
                            $subArr = [];
                            foreach ($sub['data'][2] as $subItem) {
                                $subArr[] = $this->ormQuote($subItem);
                            }
                            $subStr =
                                $sub['data'][0]
                                . ' ' . $sub['data'][1] . ' '
                                . implode(',', $subArr);
                            break;
                    }
                    break;
                case 'connect':
                    $subStr = $sub['data'];
                    break;
                case 'sub':
                    $subStr = '( ' . $this->ormMakeWhere($sub['query']) . ' )';
                    break;
                default:
                    throw new \Exception('unsupported ORM query method');
                    break;
            }
            $queryArr[] = $subStr;
        }
        return implode(" ", $queryArr);
    }

    private function ormQuote($data) {
        $type = PDO::PARAM_STR;
        switch (gettype($data)) {
            case 'boolean':
                $type = PDO::PARAM_BOOL;
                break;
            case 'integer':
                $type = PDO::PARAM_INT;
                break;
            case 'double':
            case 'string':
                $type = PDO::PARAM_STR;
                break;
            case 'NULL':
                $type = PDO::PARAM_NULL;
                break;
            case 'array':
            case 'object':
            case 'resource':
            default:
                break;
        }
        $data = self::$pdo->quote($data, $type);
        return $data;
    }

    private function _first() {
        print_r(self::$orm);
        print_r($this->ormMakeWhere(self::$orm['query']));
    }

    private function _selectOne() {
    }

    private function _select() {
    }

    private function _delete() {
    }

    private function _insert() {
    }

    private function _update($table, $mods = [], $queryVal = 0, $queryKey = 'id') {
        $query = 'update $table set $update where $queryKey=:val';
        $stat  = $this->_realQuery($query, []);
        return $stat->fetchAll();

    }
}
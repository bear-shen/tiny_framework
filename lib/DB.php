<?php namespace Lib;

/**
 * debug
 * 普通
 * DB::query('insert into spd_user (username, nickname, portrait) VALUES (:username,:nickname,:portrait)',['username'=>'asd','nickname'=>'asd','portrait'=>'asd',]);
 * k-v对
 * DB::query('insert into spd_user (:k) VALUES (:v)',[],
 *                  ['username' => 'a1', 'nickname' => 'a1', 'portrait' => 'a1',]
 * );
 * 只存在一个v，用于一个in的情况
 * DB::query('select * from spd_user where username in (:v)',[], $uidList);
 * 多个k-v对的情况
 * DB::query(
 * 'insert into spd_user (:k) VALUES (:v)',[],
 * [
 * ['username' => 'a2', 'nickname' => null, 'portrait' => 'a2',],
 * ['username' => 'a2', 'nickname' => null, 'portrait' => 'a2',],
 * ]
 * );
 * */

/**
 * @method array query($query = '', $bind = [], ...$args)
 * @method int lastInsertId()
 */
class DB {
    use FuncCallable;
    /** @var $dsn \PDO */
    public static $pdo = null;
    /** @var $dsn string */
    public static $dsn = '';
    const BathKey = '(:k)';
    const BathVal = '(:v)';
    public static $logging = false;
    public static $log     = [];
    public static $split   = 1000;//@todo 好像还是不维护这玩意比较好。。。有空再做

    public function __construct() {
        global $dbConf;
        if (!self::$pdo) {
            self::$dsn = 'mysql:dbname=' . $dbConf['db'] . ';host=' . $dbConf['host'] . ';charset=' . $dbConf['charset'] . '';
//            var_dump(self::$dsn);
            self::$pdo = new \PDO(self::$dsn, $dbConf['name'], $dbConf['pass']);
        }
    }

    /**
     * @return \PDO
     */
    public static function getPdo(): \PDO {
        $self = new self;
        return self::$pdo;
    }

    /**
     * @see query
     *
     * select * from a where a = :a
     * select * from a where :b = :a
     * insert into spd_user (:k) values (:v)
     * cast to insert into a ('','','') values ('','',''),('','','')
     * select * from spd_user where username in (:v) and pid in (:v)
     *
     * @param string $query
     * @param array $args 第一个参数为普通的绑定数据，后面的都是批量数据，根据sql文本的顺序绑定
     *
     * 相对正常的sql写法有个限制，在批量写入时语法里不应该出现 ? ，
     * 因为批量写入的函数会占用 ? 如果有的话批量的部分应该写在前面
     * 但是实际上可以通过做一次计数来绕开，但是感觉没有必要
     * 此外现在的写法多少不方便批量写入，最好想个办法优化一下
     *
     * available:
     * ['key_bind'=>'value',':key_replace'=>'value']
     * [['key_bind'=>'value',],['key_bind'=>'value',],]
     *
     * @return array
     *
     * protected 为了方便调用
     */
    private function _query($query = '', $bind = [], ...$args) {
        $datas = $args ?: [];
        $bath  = $datas;
        foreach ($bath as $k => $v) {
            if (!is_numeric($k)) {
                $bath = [$bath];
                break;
            }
        }

        //这个是强制替换sql组件的，有些不好绑定的数据就写这里面
        foreach ($bind as $key => $val) {
            if (strpos($key, ':') !== 0) continue;
            $query = str_replace($key, $val, $query);
            unset($bind[$key]);
        }
//        CliHelper::tick();
        //批量写入的部分
        $toBind = [];
        foreach ($bath as $bathItem) {
            $hitV = strpos($query, self::BathVal);
            if ($hitV !== false) {
                list($bathK, $bathV, $bindV) = $this->_generateBath($bathItem);
                $hitK = strpos($query, self::BathKey);
                if ($hitK !== false) {
                    $query = substr_replace(
                        $query,
                        $bathK,
                        $hitK,
                        strlen(self::BathKey)
                    );
                }
                //因为前面的replace，这里位置需要重新计算
                $hitV  = strpos($query, self::BathVal);
                $query = substr_replace(
                    $query,
                    $bathV,
                    $hitV,
                    strlen(self::BathVal)
                );
                foreach ($bindV as $v) {
                    $toBind[] = $v;
                }
            }
        }
        //
        $stat = self::$pdo->prepare($query);
        //绑定数据
        //批量插入的数据一定是?
        foreach ($toBind as $k => $v) {
            $key  = is_int($k) ? $k + 1 : $k;
            $type = \PDO::PARAM_STR;
            if (is_null($v)) {
                $type = \PDO::PARAM_NULL;
            } elseif (is_bool($v)) {
                $type = \PDO::PARAM_BOOL;
            }
            $stat->bindValue($key, $v, $type);
        }
//        CliHelper::tick();
        foreach ($bind as $k => $v) {
            /**
             * @see https://www.php.net/manual/zh/pdostatement.bindvalue.php
             * ? 的索引从1开始
             */
            $key  = is_int($k) ? $k + 1 : $k;
            $type = \PDO::PARAM_STR;
            if (is_null($v)) {
                $type = \PDO::PARAM_NULL;
            } elseif (is_bool($v)) {
                $type = \PDO::PARAM_BOOL;
            }
            $stat->bindValue($key, $v, $type);
        }
//        CliHelper::tick();
        $stat->setFetchMode(\PDO::FETCH_NAMED);
//        CliHelper::tick();
        if (self::$logging) {
            self::$log = [
                'query' => $query,
                'data'  => [
                    'bind' => $bind,
                    'bath' => $bath,
                ],
            ];
        }
//        var_dump($query);
//        var_dump($bath);
//        var_dump($bind);
        $stat->execute();
//        CliHelper::tick();
        return $stat->fetchAll();
    }

    /**
     * @see lastInsertId
     */
    private function _lastInsertId() {
        $stat = self::$pdo->prepare('select last_insert_id() as id;');
        $stat->setFetchMode(\PDO::FETCH_NAMED);
        $stat->execute();
        $data = $stat->fetchAll();
        return $data[0]['id'];
    }

    /**
     * @param array $data
     * @return array
     * [
     *   '(`a`,`b`,`c`)',
     *   '(?,?,?),(?,?,?),(?,?,?)',
     *   '('a','a','a'),('b','b','b'),('c','c','c')',
     * ]
     */
    private function _generateBath($data = []) {
        //data强制两层，((a:1,b:1,),(a:2,b:2,),)
        foreach ($data as $row) {
            if (is_array($row)) break;
            $data = [$data];
            break;
        }
        $keyArr     = [];
        $colSize    = 0;
        $valArr     = [];
        $valBindArr = [];
        foreach ($data as $row) {
            //key
            if (empty($keyArr)) {
                $keyArr  = array_keys($row);
                $colSize = sizeof($row);
            }
            //data
            //merge很慢，弃用
            //$valArr = array_merge($valArr, array_values($row));
            foreach ($row as $col) {
                $valArr[] = $col;
            }
//            var_dump($valArr);
            //bind
            $t            = array_fill(0, $colSize, '?');
            $valBindArr[] = '(' . implode(',', $t) . ')';
        }
//        exit();
        $keyStr     = '(`' . implode('`,`', $keyArr) . '`)';
        $valBindStr = '' . implode(',', $valBindArr) . '';
        return [
            $keyStr,
            $valBindStr,
            $valArr,
        ];
    }
}
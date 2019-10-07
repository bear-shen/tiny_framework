<?php namespace Lib;

/**
 * debug
 * $res=DB::query('insert into spd_user (username, nickname, portrait) VALUES (:username,:nickname,:portrait)',['username'=>'asd','nickname'=>'asd','portrait'=>'asd',]);
 * $res = DB::query('insert into spd_user (:k) VALUES (:v)',
 *                  ['username' => 'a1', 'nickname' => 'a1', 'portrait' => 'a1',]
 * );
 * $res     = DB::query('select * from spd_user where username in (:v)', $uidList);
 * $res = DB::query(
 * 'insert into spd_user (:k) VALUES (:v)',
 * [
 * ['username' => 'a2', 'nickname' => null, 'portrait' => 'a2',],
 * ['username' => 'a2', 'nickname' => null, 'portrait' => 'a2',],
 * ]
 * );
 * $res=DB::query('select * from spd_user where :username like :name',['name'=>'asd',':username'=>'username']);
 * var_dump($res);
 * $res=DB::query('select * from spd_user where username like :name',['name'=>'asd']);
 * var_dump($res);*/

/**
 * @method array query($query = '', $data = [],$normal=[])
 */
class DB {
    use FuncCallable;
    /** @var $dsn \PDO */
    public static $pdo = null;
    /** @var $dsn string */
    public static $dsn = '';
    const BathKey = '(:k)';
    const BathVal = '(:v)';

    public function __construct() {
        global $dbConf;
        if (!self::$pdo) {
            self::$dsn = 'mysql:dbname=' . $dbConf['db'] . ';host=' . $dbConf['host'] . '';
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
     * select * from spd_user where username in (:v)
     *
     * @param string $query
     * @param array $data 普通数据和批量插入时的批量数据
     * @param array $normal 批量插入时的绑定数据
     *
     * available:
     * ['key_bind'=>'value',':key_replace'=>'value']
     * [['key_bind'=>'value',],':key_replace'=>'value']
     *
     * @return array
     *
     * protected 为了方便调用
     */
    private function _query($query = '', $data = [],$normal=[]) {
        foreach ($data as $key => $val) {
            if (strpos($key, ':') !== 0) continue;
            $query = str_replace($key, $val, $query);
            unset($data[$key]);
        }
        if (stripos($query, self::BathVal) !== false) {
            list($bathK, $bathV, $bindV) = $this->_generateBath($data);
            $query = str_replace(
                [
                    self::BathKey,
                    self::BathVal,
                ],
                [
                    $bathK,
                    $bathV,
                ],
                $query
            );
            $data  = $bindV;
        }
//        var_dump($data);
//        var_dump($query);
//        var_dump($data);
//        exit();
        $stat = self::$pdo->prepare($query);
        foreach ($data as $k => $v) {
            $key  = is_int($k) ? $k + 1 : $k;
            $type = \PDO::PARAM_STR;
            if (is_null($v)) {
                $type = \PDO::PARAM_NULL;
            } elseif (is_bool($v)) {
                $type = \PDO::PARAM_BOOL;
            }
            $stat->bindValue($key, $v, $type);
        }
        foreach ($normal as $k => $v) {
            $key  = is_int($k) ? $k + 1 : $k;
            $type = \PDO::PARAM_STR;
            if (is_null($v)) {
                $type = \PDO::PARAM_NULL;
            } elseif (is_bool($v)) {
                $type = \PDO::PARAM_BOOL;
            }
            $stat->bindValue($key, $v, $type);
        }
        $stat->setFetchMode(\PDO::FETCH_NAMED);
        $stat->execute();
        return $stat->fetchAll();
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
            $valArr = array_merge($valArr, array_values($row));
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
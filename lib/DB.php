<?php namespace Lib;

use \PDO;

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
 * @method bool execute($query = '', $bind = [], ...$args)
 * @method array query($query = '', $bind = [], ...$args)
 * @method array queryGetOne($query = '', $bind = [], ...$args)
 *
 *
 * @method int lastInsertId()
 * @method array getErr()
 * @method int getErrCode()
 */
class DB {
    use FuncCallable;

    /** @var $dsn PDO */
    public static $pdo = null;
    /** @var $dsn string */
    public static $dsn       = '';
    public static $logging   = false;
    public static $log       = [];
    public static $split     = 1000;//@todo 好像还是不维护这玩意比较好。。。有空再做
    public static $ignoreErr = false;//不抛出错误

    const DirectReplacePrefix = ':';    //标识强制替换的参数头部
    const BathKey             = '(:k)'; //批处理的key
    const BathVal             = '(:v)'; //批处理的value
    const BathBindPrefix      = 'BTH';//批处理参数的绑定头


    public function __construct() {
        global $dbConf;
        //
        if (!self::$pdo) {
            self::$dsn = 'mysql:dbname=' . $dbConf['db'] . ';host=' . $dbConf['host'] . ';charset=' . $dbConf['charset'] . '';
//            var_dump(self::$dsn);
            self::$pdo = new PDO(self::$dsn, $dbConf['name'], $dbConf['pass']);
        }
    }

    /**
     * @return PDO
     */
    public static function getPdo(): PDO {
        if (self::$pdo) return self::$pdo;
        $self = new self;
        return self::$pdo;
    }

    /**
     * @param string $query
     *
     * @param array $bind
     * 常规绑定参数
     * ['key_bind'=>'value',':key_replace'=>'value']
     * 其中 :key_replace 可以强制替换 sql 文本中对应的绑定字段而不使用 pdo 的绑定方法
     * // 目的是用来强制替换一些 sql 组件，有些不好绑定的数据就写这里面
     * // 但是话说为什么不在 sql 上直接写完了传进来？
     *
     * @param array $args 第一个参数为普通的绑定数据，后面的都是批量数据，根据 sql 文本的顺序绑定
     *
     * # 已无效 相对正常的 sql 写法有个限制，在批量写入时语法里不应该出现 ?
     * # 已无效 因为批量写入的函数会占用 ? 如果有的话批量的部分应该写在前面
     * # 已无效 实际上可以通过做计数来绕开，但是感觉没有必要
     * 此外现在的写法多少不方便批量写入，最好想个办法优化一下
     *
     * available:
     * [['key_bind'=>'value',],['key_bind'=>'value',],]
     *
     * @return \PDOStatement
     *
     * protected 为了方便调用
     * @throws DBException
     * @see query
     *
     * select * from a where a = :a
     * select * from a where :b = :a
     * insert into spd_user (:k) values (:v)
     * cast to insert into a ('','','') values ('','',''),('','','')
     * select * from spd_user where username in (:v) and pid in (:v)
     */
    protected function _realQuery($query = '', $bind = [], ...$args) {
        $bath = $args ?: [];
        foreach ($bind as $key => $val) {
            if (strpos($key, self::DirectReplacePrefix) !== 0) continue;
            $query = str_replace($key, $val, $query);
            unset($bind[$key]);
        }
//        CliHelper::tick();
        //批量写入的部分
        $bathBind = [];
        foreach ($bath as $bathItem) {
            $hitV = strpos($query, self::BathVal);
            if ($hitV === false) break;
            //
            list($bathK, $bathV, $bindV) = $this->_generateBath($bathItem);
            //
            $hitK = strpos($query, self::BathKey);
            if ($hitK !== false) {
                $query = substr_replace(
                    $query,
                    $bathK,
                    $hitK,
                    mb_strlen(self::BathKey, 'UTF-8')
                );
            }
            //因为前面的replace，这里位置需要重新计算
            $hitV  = strpos($query, self::BathVal);
            $query = substr_replace(
                $query,
                $bathV,
                $hitV,
                mb_strlen(self::BathVal, 'UTF-8')
            );
            foreach ($bindV as $k => $v) {
                $bathBind[$k] = $v;
            }
        }
        //
        $stat = self::$pdo->prepare($query);
        //绑定数据
        //批量插入的数据一定是?
        $this->bathBind($stat, $bathBind);
        $this->bathBind($stat, $bind);
//        CliHelper::tick();
        $stat->setFetchMode(PDO::FETCH_NAMED);
//        CliHelper::tick();
        if (self::$logging) {
            self::$log[] = [
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
        //
        if (!self::$ignoreErr) {
            $ifErr = $stat->errorInfo();
            if ($ifErr[0] != '00000') {
                $errData = [
                    'err'   => $ifErr,
                    'query' => $query,
                    'bind'  => $bind,
                    'bath'  => $bath,
                ];
                throw new DBException(
                    'SQL Error on querying: "' . $errData['query'] . '", ' .
                    'info: "' . implode(':', $errData['err']) . '", ' .
                    'bind data: ' . json_encode($errData['bind'], JSON_UNESCAPED_UNICODE) . ', ' .
                    'bath data: ' . json_encode($errData['bath'], JSON_UNESCAPED_UNICODE) . '. ' .
                    '',
                    intval($ifErr[0])
                );
            }
        }
//        CliHelper::tick();

        return $stat;
    }

    protected function bathBind(&$statement, $data) {
        /**
         * @see https://www.php.net/manual/zh/pdostatement.bindvalue.php
         * ? 的索引从1开始
         */
        foreach ($data as $key => $value) {
            $key  = is_int($key) ? $key + 1 : $key;
            $type = PDO::PARAM_STR;
            if (is_null($value)) {
                $type = PDO::PARAM_NULL;
            } elseif (is_bool($value)) {
                $type = PDO::PARAM_BOOL;
            }
            $statement->bindValue($key, $value, $type);
        }
    }

    /**
     * @see lastInsertId
     */
    protected function _lastInsertId() {
        $stat = self::$pdo->prepare('select last_insert_id() as id;');
        $stat->setFetchMode(PDO::FETCH_NAMED);
        $stat->execute();
        $data = $stat->fetchAll();
        return $data[0]['id'];
    }

    protected $bathCount = 0;

    /**
     * 把数据转换成适合批处理的格式
     *
     * @param array $data
     * 输入强制两层，((a:1,b:1,),(a:2,b:2,),)
     * 如果输入一层的话也会当成两层处理
     *
     * @return array
     * [
     *   '(`a`,`b`,`c`)',                             //string key，传入数值数组的时候同样会输出，根据 sql 的占用情况处理
     *   '(':BTH0',':BTH1',':BTH2'),(':BTH3',':BTH4',':BTH5'),(':BTH6',':BTH7',':BTH8')',                   //string 绑定键
     *   ['BTH0'=>'av','BTH1'=>'bv','BTH2'=>'cv','BTH3'=>'cv','BTH4'=>'cv',], //array 绑定值
     * ]
     */
    protected function _generateBath($data = []) {
        //第一个元素不是数组说明只有一组数据
        if (!is_array(current($data))) {
            $data = [$data];
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
            $valBindStr = [];
            foreach ($row as $col) {
                $bindKey         = self::BathBindPrefix . $this->bathCount;
                $this->bathCount += 1;
                //
                $valArr[$bindKey] = $col;
                $valBindStr[]     = ':' . $bindKey;
            }

//            var_dump($valArr);
            //bind
//            $t            = array_fill(0, $colSize, '?');
            $valBindArr[] = '( ' . implode(' , ', $valBindStr) . ' )';
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

    protected function _getErr() {
        if (!self::$pdo) return [];
        return self::$pdo->errorInfo();
    }

    protected function _getErrCode() {
        if (!self::$pdo) return [];
        return self::$pdo->errorCode();
    }

    //-------------------------------------------

    //-------------------------------------------

    protected function _query($query = '', $bind = [], ...$args) {
        $stat = $this->_realQuery($query, $bind, ...$args);
        return $stat->fetchAll();
    }

    protected function _execute($query = '', $bind = [], ...$args) {
        $stat = $this->_realQuery($query, $bind, ...$args);
        return $stat->rowCount();
    }

    protected function _queryGetOne($query = '', $bind = [], ...$args) {
        $stat = $this->_realQuery($query, $bind, ...$args);
        $data = $stat->fetchAll();
        if (!empty($data)) $data = $data[0];
        return $data;
    }

}
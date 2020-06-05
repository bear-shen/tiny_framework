<?php namespace Lib;

use \PDO;

/**
 * 尝试写一个支持链式调用的 DB 类
 * @todo
 *
 */
class DBC {
    use FuncCallable;

    /** @var $dsn PDO */
    public static $pdo = null;
    /** @var $dsn string */
    public static $dsn     = '';
    public static $logging = false;
    public static $log     = [];

    public function __construct() {
        global $dbConf;
        if (!self::$pdo) {
            self::$dsn = 'mysqccl:dbname=' . $dbConf['db'] . ';host=' . $dbConf['host'] . ';charset=' . $dbConf['charset'] . '';
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

    private function limit(){}
    private function orderBy(){}
    private function where(){}
    //
    private function do(){}
    private function select(){}
    private function replace(){}
    private function insert(){}
    private function update(){}
    private function delete(){}

    private function _getErr() {
        if (!self::$pdo) return [];
        return self::$pdo->errorInfo();
    }

    private function _getErrCode() {
        if (!self::$pdo) return [];
        return self::$pdo->errorCode();
    }

}
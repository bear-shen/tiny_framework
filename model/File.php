<?php namespace Model;

use Lib\DB;
use Lib\FileHelper;
use Lib\GenFunc;

/**
 * 对文件的基础操作全部封在这里
 * 数据库同
 *
 * 切文件版本这种给node做
 * 注意：del为永久删除的接口
 *
*/
class File {
    use FileHelper;
    //
    public static function add($info){}
    //
    public static function del($id){}
    public static function move($id){}
    //
    //
    public static function get($id){}
    public static function getList($idList){}
    //
}
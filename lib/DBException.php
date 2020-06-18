<?php namespace Lib;
/**
 * 最好应该看看能不能把trace减掉一点
 * 现在这个trace前四行没有用
*/
class DBException extends \Exception {
    public function __construct($message, $code = 0, $previous = null) {
        parent::__construct($message, $code, $previous);
    }
}
<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2020/1/9
 * Time: 13:16
 */
if (!function_exists('getallheaders')) {
    function getallheaders() {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}
//在没有弄清explode到底怎么兼容多字节字符的情况下写出来的函数
if (!function_exists('mb_explode')) {
    function mb_explode($delimiter, $string, $limit = 0, $encoding = 'UTF-8') {
        $result = [];
        //
        $round = 0;
        $next  = $string;
        //
        $pos = 0;
        do {
//            var_dump($next);
            if (empty($delimiter)) {
                $pos = 1;
            } else {
                $pos = mb_strpos($next, $delimiter, 0, $encoding);
            }
            $part     = mb_substr($next, 0, $pos);
            $next     = mb_substr($next, $pos + mb_strlen($delimiter, $encoding));
            $result[] = $part;
            if (false
                || $pos === false
                || empty($next)
                || $limit > 0 && ++$round > $limit) {
                $result[] = $next;
                break;
            }
        } while (true);
        return $result;
    }
}
if (!function_exists('mb_ltrim')) {
    function mb_ltrim($string, $charList = " \t\n\r\0\x0B", $encoding = 'UTF-8') {
        $preRegexEncoding = mb_regex_encoding();
        mb_regex_encoding($encoding);
        if (is_string($charList)) {
            $charList = mb_explode('', $charList);
        }
        $string   = array_reverse(mb_explode('', $string));
        $charList = array_flip($charList);
        //
        $mod = false;
        do {
            $mod  = false;
            $last = end($string);
            if (isset($charList[$last])) {
                array_pop($string);
                $mod = true;
            }
        } while ($mod);
        mbregex_encoding($preRegexEncoding);
        return implode(array_reverse($string));
    }
}
if (!function_exists('mb_rtrim')) {
    function mb_rtrim($string, $charList = " \t\n\r\0\x0B", $encoding = 'UTF-8') {
        $preRegexEncoding = mb_regex_encoding();
        mb_regex_encoding($encoding);
        if (is_string($charList)) {
            $charList = mb_explode('', $charList);
        }
        $string   = mb_explode('', $string);
        $charList = array_flip($charList);
        //
        $mod = false;
        do {
            $mod  = false;
            $last = end($string);
            if (isset($charList[$last])) {
                array_pop($string);
                $mod = true;
            }
        } while ($mod);
        mbregex_encoding($preRegexEncoding);
        return implode($string);
    }
}

if (!function_exists('mb_trim')) {
    //性能。。。单独写
    function mb_trim($string, $charList = " \t\n\r\0\x0B", $encoding = 'UTF-8') {
        $preRegexEncoding = mb_regex_encoding();
        mb_regex_encoding($encoding);
        if (is_string($charList)) {
            $charList = mb_explode('', $charList);
        }
        $string   = mb_explode('', $string);
        $charList = array_flip($charList);
        //
        $mod = false;
        do {
            $mod  = false;
            $last = end($string);
            if (isset($charList[$last])) {
                array_pop($string);
                $mod = true;
            }
        } while ($mod);
        //
        $mod    = false;
        $string = array_reverse($string);
        do {
            $mod  = false;
            $last = end($string);
            if (isset($charList[$last])) {
                array_pop($string);
                $mod = true;
            }
        } while ($mod);
        $string = array_reverse($string);
        mbregex_encoding($preRegexEncoding);
        return implode($string);
    }
}

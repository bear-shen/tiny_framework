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
if (!function_exists('mb_explode')) {
    /**
     * 在没有弄清 explode 到底怎么兼容多字节字符的情况下写出来的函数
     * 相对官方的 explode 方法多了支持了空字符串的 explode
     * 默认值是UTF-8
     *
     * @see  https://www.php.net/manual/en/function.explode.php
     *
     * @param string $delimiter
     * @param string $string
     * @param int $limit
     * @param string $encoding
     * @return array
     */
    function mb_explode($delimiter, $string, $limit = PHP_INT_MAX, $encoding = 'UTF-8') {
        $result = [];
        //
        if (!$limit) $limit = 1;
        $round = 0;
        //
        $next = $string;
        $pos  = 0;
        //
        $delimiterLen = strlen($delimiter);
        //
        do {
            if ($delimiterLen) {
                $pos = mb_strpos($next, $delimiter, 0, $encoding);
            } else {
                $pos = 1;
            }
            //
            if ($pos === false) {
                $pos = mb_strlen($next, $encoding);
            }
            $part     = mb_substr($next, 0, $pos, $encoding);
            $result[] = $part;
            $next     = substr($next, strlen($part) + $delimiterLen);
//            echo $delimiterLen . ':' . $pos . ':' . $part . ':' . $next . "\r\n";
            if (!strlen($next)) {
                if ($delimiterLen && $pos !== false && $next !== false) $result[] = $next;
                break;
            }
            //limit
            $round += 1;
            if ($round >= $limit) {
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
        mb_regex_encoding($preRegexEncoding);
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
        mb_regex_encoding($preRegexEncoding);
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
//        var_dump($string);
        //
        $mod = false;
        do {
            $mod  = false;
            $last = end($string);
            if (isset($charList[$last])) {
//                var_dump($last . 'hit:' . $charList[$last]);
                array_pop($string);
                $mod = true;
            }
        } while ($mod);
//        var_dump($string);
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
//        var_dump($string);
        mb_regex_encoding($preRegexEncoding);
        return implode($string);
    }
}


/**
 * ---------------------------------------------------------------------
 * @see https://www.php.net/manual/en/function.uniqid.php
 *
 * Andrew Moore's note
 * ---------------------------------------------------------------------
 */
if (!function_exists('uuid_v3')) {
    function uuid_v3($namespace, $name) {
        if (!uuid_is_valid($namespace)) return false;

        // Get hexadecimal components of namespace
        $nhex = str_replace(array('-', '{', '}'), '', $namespace);

        // Binary Value
        $nstr = '';

        // Convert Namespace UUID to bits
        for ($i = 0; $i < strlen($nhex); $i += 2) {
            $nstr .= chr(hexdec($nhex[$i] . $nhex[$i + 1]));
        }

        // Calculate hash value
        $hash = md5($nstr . $name);

        return sprintf('%08s-%04s-%04x-%04x-%12s',

            // 32 bits for "time_low"
                       substr($hash, 0, 8),

            // 16 bits for "time_mid"
                       substr($hash, 8, 4),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 3
                       (hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x3000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
                       (hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,

            // 48 bits for "node"
                       substr($hash, 20, 12)
        );
    }
}
if (!function_exists('uuid_v4')) {
    function uuid_v4() {
        return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

            // 32 bits for "time_low"
                       mt_rand(0, 0xffff), mt_rand(0, 0xffff),

            // 16 bits for "time_mid"
                       mt_rand(0, 0xffff),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
                       mt_rand(0, 0x0fff) | 0x4000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
                       mt_rand(0, 0x3fff) | 0x8000,

            // 48 bits for "node"
                       mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }
}
if (!function_exists('uuid_v5')) {
    //like 1546058f-5a25-4334-85ae-e68f2a44bbaf
    function uuid_v5($namespace, $name) {
        if (!uuid_is_valid($namespace)) return false;

        // Get hexadecimal components of namespace
        $nhex = str_replace(array('-', '{', '}'), '', $namespace);

        // Binary Value
        $nstr = '';

        // Convert Namespace UUID to bits
        for ($i = 0; $i < strlen($nhex); $i += 2) {
            $nstr .= chr(hexdec($nhex[$i] . $nhex[$i + 1]));
        }

        // Calculate hash value
        $hash = sha1($nstr . $name);

        return sprintf('%08s-%04s-%04x-%04x-%12s',

            // 32 bits for "time_low"
                       substr($hash, 0, 8),

            // 16 bits for "time_mid"
                       substr($hash, 8, 4),

            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 5
                       (hexdec(substr($hash, 12, 4)) & 0x0fff) | 0x5000,

            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
                       (hexdec(substr($hash, 16, 4)) & 0x3fff) | 0x8000,

            // 48 bits for "node"
                       substr($hash, 20, 12)
        );
    }
}
if (!function_exists('uuid_is_valid')) {
    function uuid_is_valid($uuid) {
        return preg_match('/^\{?[0-9a-f]{8}\-?[0-9a-f]{4}\-?[0-9a-f]{4}\-?' .
                          '[0-9a-f]{4}\-?[0-9a-f]{12}\}?$/i', $uuid) === 1;
    }
}

/**
 * ---------------------------------------------------------------------
 * @see https://www.php.net/manual/en/function.uniqid.php
 *
 * hackan at gmail dot com's note
 * ---------------------------------------------------------------------
 */

if (!function_exists('unique_id_real')) {
    function unique_id_real($length = 16) {
        // uniqid gives 13 chars, but you could adjust it to your needs.
        if (function_exists("random_bytes")) {
            $bytes = random_bytes(ceil($length / 2));
        } elseif (function_exists("openssl_random_pseudo_bytes")) {
            $bytes = openssl_random_pseudo_bytes(ceil($length / 2));
        }
        if (empty($bytes)) {
            throw new Exception("no cryptographically secure random function available");
        }
        return substr(bin2hex($bytes), 0, $length);
    }
}
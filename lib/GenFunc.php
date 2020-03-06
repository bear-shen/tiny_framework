<?php namespace Lib;
class GenFunc {

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // string
    ////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * 过滤掉emoji表情
     *
     * http://www.jb51.net/article/88805.htm
     * http://www.cnblogs.com/xiaocongjiejie/p/5705908.html
     *
     * @param $str
     * @return string
     */
    public static function filterEmoji($str) {
        $str = preg_replace(
            [
                // Match Emoticons
                //				'/[\x{1F600}-\x{1F64F}]/u',
                // Match Miscellaneous Symbols and Pictographs
                //				'/[\x{1F300}-\x{1F5FF}]/u',
                // Match Transport And Map Symbols
                //				'/[\x{1F680}-\x{1F6FF}]/u',
                // Match Miscellaneous Symbols
                //				'/[\x{2600}-\x{26FF}]/u',
                // Match Dingbats
                //				'/[\x{2700}-\x{27BF}]/u',
                '/[\x{E000}-\x{20000}]/u',
            ]
            , '', $str);
//		$str            = preg_replace($regexEmoticons, '', $str);

        $str = preg_replace_callback(
            '/./u',
            function (array $match) {
                return strlen($match[0]) >= 4 ? '' : $match[0];
            },
            $str);
        return trim($str);
    }

    /**
     * trim数组中的字符串和普通字符串
     * @param $input
     * @return array|string
     */
    public static function trimAll($input) {
        if (is_array($input)) {
            $input = array_filter($input, function ($value) {
                return !empty($value);
            }
            );
            //
            for ($i1 = 0; $i1 < sizeof($input); $i1++) {
                if (is_string($input[$i1]))
                    $input[$i1] = trim($input[$i1]);
                else
                    $input[$i1] = self::trimAll($input[$i1]);
            }
            return $input;
        } else {
            //
            return trim($input);
        }
    }

    /**
     * 字符串转十六进制，用php的默认方法
     * @param $string
     * @return string
     */
    public static function strToHex($string) {
        $hex = strtoupper(bin2hex($string));

        return $hex;
    }

    /**
     * @deprecated
     * 抄来的……
     * 十六进制转字符串
     * @param $hex
     * @return string
     */
    public static function hexToStr($hex) {
        $string = '';
        for ($i = 0; $i < strlen($hex) - 1; $i += 2)
            $string .= chr(hexdec($hex[$i] . $hex[$i + 1]));

        return $string;
    }

    /**
     * 组装查询字符串
     * @param $inputArray array
     * @param bool $ifParse
     * @return string
     */
    public static function implode_query($inputArray, $ifParse = false) {
        if ($ifParse) {
            return http_build_query($inputArray);
        }
        $targetArray = [];
        foreach ($inputArray as $key => $value) {
            if ($ifParse) {
                $targetValue = urlencode($key) . '=' . urlencode($value);
            } else {
                $targetValue = $key . '=' . $value;
            }
            $targetArray[] = $targetValue;
        }
        return implode('&', $targetArray);
    }

    /**
     * 创建一串随机字符串
     * @param $length int
     * @param $chars  string
     * @return string
     */
    public static function createRandStr($length, $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789') {
        $str     = '';
        $charLen = strlen($chars);
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[mt_rand(0, $charLen - 1)];
        }
        return $str;
    }


    /**
     * https://www.cnblogs.com/afish/p/4167339.html
     * js escape php 实现
     * @param $string       string the string want to be escaped
     * @param $in_encoding  string
     * @param $out_encoding string
     * @return string
     */
    public static function escape($string, $in_encoding = 'UTF-8', $out_encoding = 'UCS-2') {
        $return = '';
        if (function_exists('mb_get_info')) {
            for ($x = 0; $x < mb_strlen($string, $in_encoding); $x++) {
                $str = mb_substr($string, $x, 1, $in_encoding);
                if (strlen($str) > 1) { // 多字节字符
                    $return .= '%u' . strtoupper(bin2hex(mb_convert_encoding($str, $out_encoding, $in_encoding)));
                } else {
                    $return .= '%' . strtoupper(bin2hex($str));
                }
            }
        }
        return $return;
    }

    public static function unescape($str) {
        $ret = '';
        $len = strlen($str);
        for ($i = 0; $i < $len; $i++) {
            if ($str[$i] == '%' && $str[$i + 1] == 'u') {
                $val = hexdec(substr($str, $i + 2, 4));
                if ($val < 0x7f)
                    $ret .= chr($val);
                else
                    if ($val < 0x800)
                        $ret .= chr(0xc0 | ($val >> 6)) .
                                chr(0x80 | ($val & 0x3f));
                    else
                        $ret .= chr(0xe0 | ($val >> 12)) .
                                chr(0x80 | (($val >> 6) & 0x3f)) .
                                chr(0x80 | ($val & 0x3f));
                $i += 5;
            } else
                if ($str[$i] == '%') {
                    $ret .= urldecode(substr($str, $i, 3));
                    $i   += 2;
                } else
                    $ret .= $str[$i];
        }
        return $ret;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // curl
    ////////////////////////////////////////////////////////////////////////////////////////////////


    /**
     * @deprecated
     * @see $this->curl
     *
     * 普通执行curl并返回值
     * @param $url     string
     * @param $data    array
     *
     * [
     *    get
     *    post
     * ]
     * 如果没有get或post字段的话默认全部输出到post里
     * 注意 post 传递数组会使用 multipart/form-data 方式发送，
     * URL-encoded 的字符串的话就是 application/x-www-form-urlencoded
     * ↑ php 文档是这么写的
     *
     * @param $options array
     * @return array
     *
     * [
     *    'error'=>''
     *    'success'=>
     *        'content'
     *        或者
     *        [
     *        'header'
     *        'content'
     *        ]
     *        设定了输出header时输出数组，未设定时直接输出字符串全文
     * ]
     */
    public static function exeCurl($url, $data = [], $options = []) {
//		self::dump('=================== exeCurl ===================');
//		var_dump($data);
        $ch      = curl_init();
        $options =
            $options +
            [
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_CONNECTTIMEOUT => 300,
                CURLOPT_LOW_SPEED_TIME => 300,
                CURLOPT_TIMEOUT        => 300,
                CURLOPT_HTTPHEADER     => [
                    'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.71 Safari/537.36',
                    'Accept:application/json, text/javascript, */*; q=0.01',
                    'Content-Type:application/x-www-form-urlencoded; charset=UTF-8',
                ],
            ];
//			var_dump($options);
        curl_setopt_array($ch, $options);
        //处理传输数据
        if (
            !empty($data) && empty($data['post']) && empty($data['get'])
        ) {
            $data['post'] = $data;
        }
        $data = $data + [
                'get'  => [],
                'post' => [],
            ];
//		var_dump($data);
        //输出get
        if (strpos($url, '?')) {
            $url .= '&' . self::implode_query($data['get']);
        } else {
            $url .= '?' . self::implode_query($data['get']);
        }
        //输出post
        if (isset($data['post'])) {
            curl_setopt_array(
                $ch,
                [
                    CURLOPT_POST       => 1,
                    CURLOPT_POSTFIELDS => $data['post'],
                ]
            );
        }
//		self::dump($data);
//			dump($data);
        //输出url
        curl_setopt($ch, CURLOPT_URL, $url);
        //执行
        //	dump('before');
        $result = curl_exec($ch);
        //	dump($result);
        //	dump(curl_getinfo($ch));
        //	dump('after');
        if (isset($options[CURLOPT_HEADER]) && $options[CURLOPT_HEADER]) {
            $header  = substr($result, 0, curl_getinfo($ch)['header_size']);
            $content = substr($result, curl_getinfo($ch)['header_size']);
            return [
                'success' =>
                    [
                        'header'  => $header,
                        'content' => $content
                    ]
            ];
        }
        //	dump(curl_getinfo($ch));
        if (curl_error($ch) != '') {
//			self::dump('==--== curl_errors ==--==', 'full');
//			self::dump(curl_errno($ch), 'full');
//			self::dump(curl_error($ch), 'full');
            return ['error' => curl_errno($ch) . '\t' . curl_error($ch)];
        }
        curl_close($ch);
        return ['success' => $result];
    }

    /**
     * @deprecated
     * @see $this->curlMulti
     *
     * 同时执行多个curl
     * @param $url        array
     *
     * @param $data       array
     *
     * 多组datas时
     * [[
     *       get =>[]
     *       post=>[]
     * ]]
     * 单组时可以输入，get或post至少有一个，用作判断数量的依据
     * [
     *    get =>[]
     *    post=>[]
     * ]
     *
     * @param $option     array
     *
     * options判断层级略复杂……
     * 多组的表示
     * [[
     *       option1 =>1
     *       option2 =>[]
     * ]]
     * 单组可以通过下面的方式表示
     * [
     *    option1 =>1
     *    option2 =>[]
     * ]
     * 但是注意option不能全部都要求输入数组型数据，如只有 CURLOPT_HTTPHEADER 这样的，
     * 否则会被当成多组数据【这里判断还不确定要怎么写，手册上没讲的很细不知道怎么下手】
     *
     * @param $queryLimit int
     *                    分组执行，每次执行的上限
     *
     * @return array
     *    [
     *       'success' =>
     *             [
     *                'url'=>
     *                'data'=>
     *                'content'=>
     *             ],
     *       'error'   => [],
     *    ]
     * 正确返回array，即使只有一个也返回array
     * success和error均输出为array
     * 单个curl出现错误或是多个出现错误都返回在error里
     * 这里有一个问题，即无法知道实际curl的参数
     * 操作的话有两套方案，一套是把输入数据返回到输出数据里
     * 另一套是强制增加一个索引，但是索引默认是无视的，输出是使用数字索引
     * 姑且认为索引是正常的，也就是你令a[3]=0则a[3]=0，但是总之这是不靠谱的……
     * 所以返回参数就很多层了……
     * data中可以添加一些其他的参数，会在数组中返回
     *
     */
    public static function exeMultiCurl(
        $url, $data = [], $option = [], $queryLimit = 50, $nocheck = false
    ) {
        if (empty($url)) return ['error' => []];
//		self::dump('=================== exeMultiCurl ===================');
//		self::dump('time');
        $default = [
            'options' => [
                CURLOPT_RETURNTRANSFER => 1,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_CONNECTTIMEOUT => 300,
                CURLOPT_LOW_SPEED_TIME => 300,
                CURLOPT_TIMEOUT        => 300,
                CURLOPT_HTTPHEADER     => [
                    'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.71 Safari/537.36',
                    'Accept:application/json, text/javascript, */*; q=0.01',
                    'Content-Type:application/x-www-form-urlencoded; charset=UTF-8',
                ],
            ]
        ];
        //	$multiSwitch = $multi + ['url' => false, 'data' => true, 'option' => false,];

        $fullData = [
            'url'    => [],
            'data'   => [],
            'option' => [],
        ];
        $size     = 0;
        ////////////////////////////////////////////////////////////////
        //规范化传输数据
        if (!$nocheck) {
            //url
            if (is_string($url)) {
                $url = [$url];
            } else {
                $url = array_values($url);
            }
            //data
            if (empty($data)) {
                $data = [[
                             'get'  => [],
                             'post' => [],
                         ]];
            } else {
                if (isset($data['get']) || isset($data['post'])) {
                    $data = [$data];
                }
                $data    = array_values($data);
                $dataLen = sizeof($data);
                for ($i1 = 0; $i1 < $dataLen; $i1++) {
                    $data[$i1] += [
                        'get'  => [],
                        'post' => [],
                    ];
                }
            }
            //options
//			var_dump($option);
            if (empty($option)) {
                $option = [[]];
            } else {
                foreach ($option as $val) {
                    if (!is_array($val)) {
                        $option = [$option];
                        break;
                    }
                }
                $option    = array_values($option);
                $optionLen = sizeof($option);
                for ($i1 = 0; $i1 < $optionLen; $i1++) {
//					var_dump($option);
                    $option[$i1] = $option[$i1] + $default['options'];
                }
            }
            if (empty($queryLimit)) {
                $queryLimit = 50;
            }
            ////////////////////////////////////////////////////////////////
            //整合数据
            $size = max(sizeof($url), sizeof($data), sizeof($option));
            for ($ia = 0; $ia < $size; $ia++) {
                $fullData['url'][]    = isset($url[$ia]) ? $url[$ia] : $url[0];
                $fullData['data'][]   = isset($data[$ia]) ? $data[$ia] : $data[0];
                $fullData['option'][] = isset($option[$ia]) ? $option[$ia] : $option[0];
            }
            unset($url);
            unset($data);
            unset($option);
        } else {
            $size               = sizeof($url);
            $fullData['url']    = $url;
            $fullData['data']   = $data;
            $fullData['option'] = $option;
        }
        $result = [
            'success' => [],
            'error'   => [],
        ];
        //	dump($fullData);
        ////////////////////////////////////////////////////////////////
        //数据较多时，分组，递归
        if ($size > $queryLimit) {
            for ($ia = 0; $ia < ceil($size / $queryLimit); $ia++) {
                $singleResult = self::exeMultiCurl(
                    array_slice($fullData['url'], $ia * $queryLimit, $queryLimit),
                    array_slice($fullData['data'], $ia * $queryLimit, $queryLimit),
                    array_slice($fullData['option'], $ia * $queryLimit, $queryLimit),
                    $queryLimit,
                    true
                );
                $result       = [
                    'success' => array_merge($result['success'], $singleResult['success']),
                    'error'   => array_merge($result['error'], $singleResult['error']),
                ];
            }
            return $result;
        }
        ////////////////////////////////////////////////////////////////
        //分别输出get和post到url和option
        for ($ia = 0; $ia < $size; $ia++) {
            //
            $url    = $fullData['url'][$ia];
            $data   = $fullData['data'][$ia];
            $option = $fullData['option'][$ia];
            //
            //		dump($data);
            if (strpos($url, '?')) {
                $url .= '&' . self::implode_query($data['get']);
            } else {
                $url .= '?' . self::implode_query($data['get']);
            }
            $option[CURLOPT_URL] = $url;
            if (isset($data['post']) && sizeof($data['post']) > 0) {
                $option[CURLOPT_POST]       = 1;
                $option[CURLOPT_POSTFIELDS] = $data['post'];
            }
            //
            $fullData['url'][$ia]    = $url;
            $fullData['data'][$ia]   = $data;
            $fullData['option'][$ia] = $option;
        }
        ////////////////////////////////////////////////////////////////
        //创建curl类
        $cm      = curl_multi_init();
        $chArray = [];
        for ($ia = 0; $ia < $size; $ia++) {
            $ch = curl_init($fullData['url'][$ia]);
            curl_setopt_array(
                $ch,
                $fullData['option'][$ia]
            );
            $chArray[] = $ch;
        }
        ////////////////////////////////////////////////////////////////
        //执行，大于1使用cm，其他使用普通curl
        if ($size > 1) {
            //准备cm
            foreach ($chArray as $ch) {
                curl_multi_add_handle($cm, $ch);
            }
            //执行
            $running = null;
//			$status  = 0;
            do {
                $status = curl_multi_exec($cm, $running);
                usleep(10000);//0.01s
            } while ($running > 0);
            //获取结果
            if ($status > 0) {
                $result['error'] = [curl_multi_strerror($status)];
                return $result;
            } else {
                for ($ia = 0; $ia < $size; $ia++) {
                    if (!empty(curl_error($chArray[$ia]))) {
                        $result['error'][] =
                            [
                                'url'     => $fullData['url'][$ia],
                                'data'    => $fullData['data'][$ia],
                                'content' => curl_errno($chArray[$ia]) . '\t' . curl_error($chArray[$ia]),
                            ];
//						self::dump('==--== curl_errors ==--==', 'full');
//						self::dump(curl_errno($chArray[0]), 'full');
//						self::dump(curl_error($chArray[0]), 'full');
                        //					array_push(
                        //						$result,
                        //						'error:' . curl_errno($chArray[0]) . '\t' . curl_error($chArray[0])
                        //					);
                    } else {
                        $result['success'][] =
                            [
                                'url'     => $fullData['url'][$ia],
                                'data'    => $fullData['data'][$ia],
                                'content' => curl_multi_getcontent($chArray[$ia]),
                            ];
                    }
                }
            }
            //关闭
            foreach ($chArray as $ch) {
                curl_multi_remove_handle($cm, $ch);
            }
            curl_multi_close($cm);
        } else {
            $chResult          = curl_exec($chArray[0]);
            $result['success'] = [[
                                      'url'     => $fullData['url'][0],
                                      'data'    => $fullData['data'][0],
                                      'content' => $chResult,
                                  ]
            ];
            if (curl_error($chArray[0]) != '') {
//				self::dump('==--== curl_errors ==--==', 'full');
//				self::dump(curl_errno($chArray[0]), 'full');
//				self::dump(curl_error($chArray[0]), 'full');
                $result['error'] = [curl_errno($chArray[0]) . '\t' . curl_error($chArray[0])];

            }
        }
//		self::dump('time');
        return $result;

    }

    public static $defCurlConf = [
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_CONNECTTIMEOUT => 120,
        CURLOPT_LOW_SPEED_TIME => 120,
        CURLOPT_TIMEOUT        => 120,
        //        CURLOPT_HTTPHEADER     => [
        //            'User-Agent: Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/54.0.2840.71 Safari/537.36',
        //            'Accept:application/json, text/javascript, */*; q=0.01',
        //            'Content-Type:application/x-www-form-urlencoded; charset=UTF-8',
        //        ],
        CURLOPT_RESOLVE        => ['tieba.baidu.com:80:180.97.34.146'],
    ];

    /**
     * @param array|string $config
     *
     * [curl_opt] or 'url'
     *
     * @return mixed
     */
    public static function curl($config = []) {
        $ch = curl_init();
        curl_setopt_array($ch, self::$defCurlConf);
        //
        $opt = [];
        if (is_string($config)) {
            $opt = [CURLOPT_URL => $config];
        } else {
            $opt = $config;
        }
        //
        curl_setopt_array($ch, $opt);
        //
        $res = curl_exec($ch);
        //
        curl_close($ch);
        return $res;
    }

    /**
     * @param $config array
     *
     * ['url','url','url',]
     * [[curl_opt],[curl_opt],[curl_opt],]
     *
     * @param $global array
     *
     * [curl_opt]
     *
     * @param $withInfo bool
     * @param $truncate integer|bool
     *
     * @return array
     *
     * $withInfo => false [txt,txt,txt]
     * $withInfo => true [['data'=>'','info'=>[]],['data'=>'','info'=>[]],]
     */
    public static function curlMulti($config = [], $global = [], $withInfo = false, $truncate = false) {
        if (is_int($truncate) && sizeof($config) > $truncate) {
            $result = [];
            for ($i1 = 0; $i1 < ceil(sizeof($config) / $truncate); $i1++) {
                $subConfig    = array_slice($config, $truncate * $i1, $truncate);
                $truncateList = self::curlMulti($subConfig, $global, $withInfo);
                foreach ($truncateList as $item) {
                    $result[] = $item;
                }
            }
            return $result;
        }
        $chList = [];
        foreach ($config as $row) {
            $ch = curl_init();
            //
            curl_setopt_array($ch, self::$defCurlConf);
            //
            if (!empty($global)) {
                curl_setopt_array($ch, $global);
            }
            //
            $opt = $row;
            if (is_string($row)) {
                $opt = [CURLOPT_URL => $row];
            }
            curl_setopt_array($ch, $opt);
            //
            $chList[] = $ch;
        }
        //
        $mh = curl_multi_init();
        foreach ($chList as $ch) {
            curl_multi_add_handle($mh, $ch);
        }
        //
        $act = null;
        do {
            curl_multi_exec($mh, $act);
        } while ($act > 0);
        //
        $result = [];
        foreach ($chList as $ch) {
            $data = curl_multi_getcontent($ch);
//            var_dump(curl_multi_info_read($ch));
//            var_dump(curl_getinfo($ch));
            curl_multi_remove_handle($mh, $ch);
            if ($withInfo) {
                $data = [
                    'data' => $data,
                    'info' => curl_getinfo($ch),
                ];
            }
            $result[] = $data;
        }
        curl_multi_close($mh);
//        exit();
        return $result;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // array
    ////////////////////////////////////////////////////////////////////////////////////////////////
    /**
     * 根据给入的列进行排序
     *
     * 只指定 $valueName
     * 或 $valueArray 不为数组
     * 或是 $valueArray 与 $inputArray 长度不一时
     * 套用 asort|arsort 函数排序
     *
     * @param $inputArray array
     * @param $valueName  string
     * @param $valueArray array|string|boolean
     *
     * 为string时可以输入'asc','desc'，输入任意默认'asc',正向排序
     * 为boolean时可以输入true,false，输入任意默认true,正向排序
     * 为integer时可以输入1,0，输入任意默认1,正向排序
     *
     * @return array
     */
    /*public static function sortByIndexValue($inputArray, $valueName, $valueArray = []) {
//		self::dump('========= sortByIndexValue ==========');
        $resultArray = [];
        if (
            gettype($valueArray) != 'array'
            || sizeof($inputArray) != sizeof($valueArray)
        ) {
            $targetValueArray = array_column($inputArray, $valueName);
            if (
                $valueArray == 'desc'
                || $valueArray == false
                || $valueArray == 0
            ) {
                arsort($targetValueArray);
            } else {
                asort($targetValueArray);
            }
            $valueArray = $targetValueArray;
        }
        foreach ($valueArray as $ia => $valueA) {
            foreach ($inputArray as $ib => $valueB) {
                //
                if ($valueB[$valueName] == $valueA) {
                    if (is_numeric($ib)) {
                        array_push($resultArray, $valueB);
                    } else {
                        $resultArray[$ib] = $valueB;
                    }
                }
            }
        }
        //	dump('========= inputArray ==========');
        //	dump($inputArray);
        //	dump('========= valueArray ==========');
        //	dump($valueArray);
        return $resultArray;
    }*/

    /**
     * 将某一列转化为key，主要是为了方便后期添加一些查重或者排错方式因此没用array_column
     * @param $array          array
     * @param $key            string
     * @param $groupKey {string|boolean}
     *                        如果设定了groupkey，会生成一个二维数组，第二维度为groupKey所在列的值
     *                        也就是不会覆盖掉重复的内容而是并行的新加一个
     *                        如果为false则不生成二维数组
     *                        如果为true则为数字索引
     * @param $placeHolder_L0 string 给索引添加字符串前缀
     *
     * @return array
     */
    public static function value2key($array, $key, $groupKey = false, $placeHolder_L0 = '') {
        //区分分类的类型
        $group = false;
        if (
        !empty($groupKey)
//			(gettype($groupKey) == 'string' && strlen($groupKey) > 0) ||
//			(gettype($groupKey) == 'boolean' && $groupKey)
        ) {
            $group = true;
        }
        //
        $newArray = [];
        //
        $array = array_values($array);
        if ($group) {
            //有二级索引
            if (is_bool($groupKey)) {
                foreach ($array as $row) {
                    $l0Key = $placeHolder_L0 . $row[$key];
                    if (!isset($newArray[$l0Key])) $newArray[$l0Key] = [];
                    $newArray[$l0Key][] = $row;
                }
//				for ($i1 = 0; $i1 < sizeof($array); $i1++) {
//					$l0Key = $placeHolder_L0 . $array[$i1][$key];
//					if (@gettype($newArray[$l0Key]) == 'NULL') {
//						$newArray[$l0Key] = [];
//					}
//					array_push($newArray[$l0Key], $array[$i1]);
//				}
                //		foreach ($array as $k => $v) {
                //			$l0Key = $placeHolder_L0 . $v[ $key ];
                //			if (@gettype($newArray[ $l0Key ]) == 'NULL') {
                //				$newArray[ $l0Key ] = [];
                //			}
                //			array_push($newArray[ $l0Key ], $v);
                //		}
            } else {
                foreach ($array as $row) {
                    $l0Key = $placeHolder_L0 . $row[$key];
                    if (!isset($newArray[$l0Key])) $newArray[$l0Key] = [];
                    $newArray[$l0Key][$row[$groupKey]] = $row;
                }
//				for ($i1 = 0; $i1 < sizeof($array); $i1++) {
//					$newArray[$placeHolder_L0 . $array[$i1][$key]][$array[$i1][$groupKey]] = $array[$i1];
//				}
                //		foreach ($array as $k => $v) {
                //			$newArray[ $placeHolder_L0 . $v[ $key ] ][ $v[ $groupKey ] ] = $v;
                //		}
            }
        } else {
            //没索引
            foreach ($array as $row) {
                $newArray[$placeHolder_L0 . $row[$key]] = $row;
            }
//			for ($i1 = 0; $i1 < sizeof($array); $i1++) {
//				$newArray[$placeHolder_L0 . $array[$i1][$key]] = $array[$i1];
//			}
            //		foreach ($array as $k => $v) {
            //			$newArray[ $placeHolder_L0 . $v[ $key ] ] = $v;
            //		}
        }

        return $newArray;
    }

    public static function col2key($array, $key, $groupKey = false, $placeHolder_L0 = '') {
        return self::value2key($array, $key, $groupKey, $placeHolder_L0);
    }


    /**
     * 打laravel代码抄的，名字也不管了，反正就这个东西……
     * @param $array array
     * @param $keys array
     * @return array
     */
    public static function array_only($array, $keys) {
        return array_intersect_key($array, array_flip((array)$keys));
    }

    public static function array_pluck($array, $key) {
        $result = [];
        foreach ($array as $item) {
            $result[] = $item[$key];
        }
        return $result;
    }
    ////////////////////////////////////////////////////////////////////////////////////////////////
    // cookie
    ////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * 处理cookie文件内容到数组
     * 只输出key和value
     * @param $cookieFileContent
     * @return array
     */
    public static function parseCookieArray($cookieFileContent) {
        $cookieFileContent = preg_replace(
            [
                '/#(.*)(\r|\n)/im',
            ],
            [
                '',
            ],
            $cookieFileContent
        );
        $cookieArray       = explode('\n', $cookieFileContent);
        $targetArray       = array();
        foreach ($cookieArray as $cookie) {
            if (strlen($cookie) > 1) {
                $currentArray = explode('\t', $cookie);
                //			array_push($targetArray, $currentArray);
                if (sizeof($currentArray) == 7) {
                    $targetArray[] = [
                        $currentArray[5], $currentArray[6]
                    ];
                }
            }
        }
        $targetContent = $targetArray;

        return $targetContent;
    }

    /**
     * 处理cookie的[k,v]数组到字符串
     * @param $cookieArray
     * @return string
     */
    public static function parseCookie2String($cookieArray) {
        $targetArray = array();

        foreach ($cookieArray as $k0 => $v0) {
            $targetArray[] = implode('=', $cookieArray[$k0]);
        }
        $targetString = implode(';', $targetArray);

        return $targetString;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // dump & log
    ////////////////////////////////////////////////////////////////////////////////////////////////

    private static $tickStart = false;
    private static $tickPrev  = 0;

    /**
     * 计时器
     * @param bool $sum
     * @return float
     */
    public static function getTick($sum = false) {
        $now = microtime(true);
        //
        self::$tickStart = self::$tickStart ?: $now;
        if ($sum) return $now - self::$tickStart;
        //
        $delta          = (self::$tickPrev == 0) ? 0 : $now - self::$tickPrev;
        self::$tickPrev = $now;
        return $delta;
    }


    public static function kmgt($size) {
        $unitArr = ['B', 'KB', 'MB', 'GB', 'TB'];
        $count   = 0;
        $under0  = false;
        if ($size < 0) {
            $under0 = true;
            $size   *= -1;
        }
        while ($size > 1024 && $count++ < sizeof($unitArr)) {
            $size /= 1024.0;
        }
        if ($under0) $size *= -1;
        $size = round($size, 3) . $unitArr[$count];
        return $size;
    }

    private static $memPrev = 0;

    /**
     * 内存计数，这里global是完全的global，不是首次调用开始
     * @param bool $global
     * @return string
     */
    public static function memoryTick($global = false) {
        $currentMem = memory_get_usage();
        if ($global) return self::kmgt($currentMem);
        $delta         = $currentMem - self::$memPrev;
        self::$memPrev = $currentMem;
        return self::kmgt($delta);
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // file
    ////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * 获取文件的文件夹路径
     * /和\均可
     * 找不到则自动返回全文
     * @param $filePath  string
     * @param $withSlash boolean
     * @return string
     */
    public static function getDir($filePath, $withSlash = true) {
        return dirname($filePath);
        /*//	dump('============ getDir ============', 'title');
        //	dump($filePath);
        //	dump(strrpos($filePath, '/'));
        $pathIndexA = strrpos($filePath, '/');
        $pathIndexB = strrpos($filePath, '\\');
        $pathIndex  = max([$pathIndexA, $pathIndexB]);
        //
        if ($pathIndex == 0) {
            return $filePath;
        }

        $targetPath = substr($filePath, 0, $pathIndex + ($withSlash ? 1 : 0));

        return $targetPath;*/
    }

    /**
     * 获取输入文件的内容
     * 包括执行的php
     * @param $targetFilePath
     * @return string
     */
    public static function getTmp($targetFilePath) {
        ob_start();
        @require($targetFilePath);
        $content = ob_get_contents();
        ob_clean();
        return $content;
    }

    /**
     * 遍历文件夹
     * @param string $dirPath
     * @param bool $step
     * @return array
     */
    public static function getDirFile($dirPath, $step = false) {
        $list = [];
        $scan = scandir($dirPath);
        foreach ($scan as $item) {
            if ($item == '.' || $item == '..') continue;
            $cur    = $dirPath . '/' . $item;
            $list[] = $cur;
            if (!is_dir($cur)) continue;
            if ($step === false || (!is_bool($step) && $step <= 0))
                //进入文件夹
                $sub = self::getDirFile($cur, is_bool($step) ? $step : $step - 1);
            foreach ($sub as $subItem) {
                $list[] = $subItem;
            }
        }
        return $list;
    }


    /**
     * 遍历文件夹
     * @param string|array $dir 传入 'path' 遍历文件夹，传入 [root,path] 添加文件夹的前缀，主要是用于相对路径
     * @param integer $mode absolute = 1|relative = 0|name only = -1
     * @param bool|integer $step
     * @return array
     */
    function scanDirPlus($dir, $mode = 1, $step = false) {
        $fList   = [];
        $absRoot = is_array($dir) ? rtrim($dir[0], '/\\') . DIRECTORY_SEPARATOR . ltrim($dir[1], '/\\') : trim($dir, '/\\');
        $scan    = scandir($absRoot);
        foreach ($scan as $item) {
            if ($item == '.' || $item == '..') continue;
            //
            $subAbsRoot = $absRoot . DIRECTORY_SEPARATOR . $item;
            $fList[]    = $item;
            if (!is_dir($subAbsRoot)) continue;
            if (is_int($step) && $step <= 0) continue;
            //遍历子目录使用相对地址，主要仅文件名模式需要单独处理mode
            $subList = scanDirPlus(
                [
                    is_array($dir) ? $absRoot : $dir,
                    $item
                ], $mode == -1 ? $mode : 0, $step ? $step - 1 : $step
            );
            foreach ($subList as $subItem) {
                $fList[] = $subItem;
            }
        }
        //处理文件名
        switch ($mode) {
            case 0:
                for ($i1 = 0; $i1 < sizeof($fList); $i1++) {
                    $fList[$i1] = (is_array($dir) ? $dir[1] . DIRECTORY_SEPARATOR : '') . $fList[$i1];
                }
                break;
            case 1:
                //绝对路径在内部导出的是相对路径，所以这边直接补齐就行了
                for ($i1 = 0; $i1 < sizeof($fList); $i1++) {
                    $fList[$i1] = $absRoot . DIRECTORY_SEPARATOR . $fList[$i1];
                }
                break;
            case -1:
                break;
        }
        return $fList;
    }

    /**
     * @param $imgType
     * @return string
     */
    public static function getImgTypeString($imgType) {
        $target = '';
        switch ($imgType) {
            case IMAGETYPE_GIF:
                $target = 'GIF';
                break;
            case IMAGETYPE_JPEG:
                $target = 'JPEG';
                break;
            case IMAGETYPE_PNG:
                $target = 'PNG';
                break;
            case IMAGETYPE_SWF:
                $target = 'SWF';
                break;
            case IMAGETYPE_PSD:
                $target = 'PSD';
                break;
            case IMAGETYPE_BMP:
                $target = 'BMP';
                break;
            case IMAGETYPE_TIFF_II:
                $target = 'TIFF_II';
                break;
            case IMAGETYPE_TIFF_MM:
                $target = 'TIFF_MM';
                break;
            case IMAGETYPE_JPC:
                $target = 'JPC';
                break;
            case IMAGETYPE_JP2:
                $target = 'JP2';
                break;
            case IMAGETYPE_JPX:
                $target = 'JPX';
                break;
            case IMAGETYPE_JB2:
                $target = 'JB2';
                break;
            case IMAGETYPE_SWC:
                $target = 'SWC';
                break;
            case IMAGETYPE_IFF:
                $target = 'IFF';
                break;
            case IMAGETYPE_WBMP:
                $target = 'WBMP';
                break;
            case IMAGETYPE_XBM:
                $target = 'XBM';
                break;
        }
        $target = strtolower($target);
        return $target;
    }

    ////////////////////////////////////////////////////////////////////////////////////////////////
    // other
    ////////////////////////////////////////////////////////////////////////////////////////////////

    /**
     * @see https://stackoverflow.com/questions/18008135/is-serverrequest-scheme-reliable
     */
    public static function getRequestUrl() {
        if (
            (isset($_SERVER['REQUEST_SCHEME']) && $_SERVER['REQUEST_SCHEME'] == 'https')
            || (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on')
            || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443')
        ) {
            $server_request_scheme = 'https';
        } else {
            $server_request_scheme = 'http';
        }
        return $server_request_scheme
               . '://'
               . $_SERVER['HTTP_HOST']
               . $_SERVER['REQUEST_URI'];
    }

    /**
     * 获取源路径，不可靠
     * @return string|boolean
     */
    public static function getReferrer() {
        $result = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : false;
        return $result;
    }

    /**
     * 恢复 parse_url 的数组
     * @see http://php.net/manual/zh/function.parse-url.php
     * @param $parsed_url array
     * @return string
     */
    public static function unparse_url($parsed_url) {
        $scheme   = isset($parsed_url['scheme']) ? $parsed_url['scheme'] . '://' : '';
        $host     = isset($parsed_url['host']) ? $parsed_url['host'] : '';
        $port     = isset($parsed_url['port']) ? ':' . $parsed_url['port'] : '';
        $user     = isset($parsed_url['user']) ? $parsed_url['user'] : '';
        $pass     = isset($parsed_url['pass']) ? ':' . $parsed_url['pass'] : '';
        $pass     = ($user || $pass) ? "$pass@" : '';
        $path     = isset($parsed_url['path']) ? $parsed_url['path'] : '';
        $query    = isset($parsed_url['query']) ? '?' . $parsed_url['query'] : '';
        $fragment = isset($parsed_url['fragment']) ? '#' . $parsed_url['fragment'] : '';
        return "$scheme$user$pass$host$port$path$query$fragment";
    }

    /**
     * 获取请求用户的ip
     * 现在在已知的特定情况下会出现获取到多个ip的情况，应该和cdn还有各种代理有关系
     * 会影响到支付或者一些需要使用ip作为参数的操作，但是还没有想好怎么做更细致的处理
     */
    public static function getUserIp() {
        $ip = false;
        if (isset($HTTP_SERVER_VARS)) {
            //5.4前
            if (isset($HTTP_SERVER_VARS['HTTP_X_FORWARDED_FOR'])) {
                $ip = $HTTP_SERVER_VARS['HTTP_X_FORWARDED_FOR'];
            } elseif (isset($HTTP_SERVER_VARS['HTTP_CLIENT_IP'])) {
                $ip = $HTTP_SERVER_VARS['HTTP_CLIENT_IP'];
            } elseif (isset($HTTP_SERVER_VARS['REMOTE_ADDR'])) {
                $ip = $HTTP_SERVER_VARS['REMOTE_ADDR'];
            }
            if ($ip) return $ip;
        }
        if (isset($_SERVER)) {
            //4.1开始存在
            if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
            } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {
                $ip = $_SERVER['HTTP_CLIENT_IP'];
            } elseif (isset($_SERVER['REMOTE_ADDR'])) {
                $ip = $_SERVER['REMOTE_ADDR'];
            }
            if ($ip) return $ip;
        }
        //↓这种蜜汁写法性能会更高，但是总之担心幺蛾子就不管了
//			$ip = getenv('HTTP_X_FORWARDED_FOR') ?:
//					getenv('HTTP_CLIENT_IP') ?:
//					getenv('REMOTE_ADDR') ?:
// 					false;
        if (getenv('HTTP_X_FORWARDED_FOR')) {
            $ip = getenv('HTTP_X_FORWARDED_FOR');
        } elseif (getenv('HTTP_CLIENT_IP')) {
            $ip = getenv('HTTP_CLIENT_IP');
        } elseif (getenv('REMOTE_ADDR')) {
            $ip = getenv('REMOTE_ADDR');
        }
        return $ip;
    }

    public static function isEmail($email) {
        return preg_match('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i', $email);
    }

    public static function isMobile($mobile) {
        return preg_match('/^1[3-9][0-9]{9}$/', $mobile);
    }

    /**
     * 判断是否是移动浏览器
     * 因为有一个isMobile了，所以加个UA作为区分
     */
    public static function isUAMobile() {
        return preg_match('/(iPhone|iPad|iPod|iOS|Android)/i', $_SERVER['HTTP_USER_AGENT']);
    }

    /**
     * 判断是否是微信
     */
    public static function isWeixin() {
        if (strpos($_SERVER['HTTP_USER_AGENT'], 'MicroMessenger') !== false) {
            return true;
        }
        return false;
    }

    public static function isIos() {
        if (stripos($_SERVER['HTTP_USER_AGENT'], 'iphone') !== false
            || stripos($_SERVER['HTTP_USER_AGENT'], 'ipad') !== false
        ) {
            return true;
        }
        return false;
    }

    public static function isAndroid() {
        if (stripos($_SERVER['HTTP_USER_AGENT'], 'iphone') !== false
            || stripos($_SERVER['HTTP_USER_AGENT'], 'ipad') !== false
        ) {
            return false;
        }
        return true;
    }

    /**
     * @param string $dateStr
     * @param string $to
     * @return string
     */
    public static function timeTrans($dateStr, $to = 'Y-m-d H:i:s') {
        return date($to, strtotime($dateStr));
    }

    public static function humanTime($timestamp) {
        $cur   = time();
        $today = strtotime('today midnight');
        $delta = $cur - $timestamp;
        //
        $result = '';
        if ($delta < 359) $result = '刚刚';
        elseif ($delta < 30 * 60) $result = ceil($delta / 60) . '分钟前';
        elseif ($delta < 60 * 60) $result = '半小时前';
        elseif ($timestamp > $today) $result = ceil($delta / 3600) . '小时前';
        elseif ($timestamp > $today - 86400) $result = '昨天';
        elseif ($timestamp > $today - 86400 * 2) $result = '前天';
        else $result = date('m月d日', $timestamp);
        //
        return $result;
    }

    public static function humanNum($num) {
        if ($num < 10000) return $num;
        elseif ($num < 10000000) return round($num / 10000) . '万';
        elseif ($num < 100000000) return round($num / 10000000) . '千万';
        else return round($num / 100000000) . '亿';
    }

    /**
     * 清理多余html代码
     * @param string $content
     * @return string
     */
    public static function clearHtml($content = '') {
        if (empty($content)) return '';
        $regex   = [
            '/<\/*\w+[^>]*?>/i',
        ];
        $content = trim(preg_replace($regex, [], $content));
        return $content;
    }

    /**
     * 拼音的模糊音计算
     * 因为太麻烦所以丢这里面了，以后如果要用的话copy这里就行
     * @param $spell string 可以带有平仄的数字
     *
     * 建议迭代一次，比如san sang shan shang
     *
     * @return array
     *
     * ['spell','spell','spell',]
     */
    public static function fuzzySpellCollector($spell) {
        $list   = [
            ['s', 'sh', 'head'],
            ['c', 'ch', 'head'],
            ['z', 'zh', 'head'],
            ['l', 'n', 'head'],
            ['f', 'h', 'head'],
            ['r', 'l', 'head'],
            ['an', 'ang', 'foot'],
            ['en', 'eng', 'foot'],
            ['in', 'ing', 'foot'],
            ['ian', 'iang', 'foot'],
            ['uan', 'uang', 'foot'],
        ];
        $result = [];
        foreach ($list as $fuzzy) {
            $chk = self::fuzzySpellChecker($spell, $fuzzy);
            if (empty($chk)) continue;
            $result[] = $chk;
        }
        return $result;
    }

    private static function fuzzySpellChecker($spell, $fuzzy = ['', '', 'head']) {
        //长度不等的时候需要判断哪个最长，短文本在前
        //英文域不需要mb
        if (strlen($fuzzy[0]) > strlen($fuzzy[1])) {
            $sw       = $fuzzy[0];
            $fuzzy[0] = $fuzzy[1];
            $fuzzy[1] = $sw;
        }
        //拼音后缀可能会有1-4的平仄，这里先去掉然后重新加上
        $digi = [];
        preg_match('/\d+/i', $spell, $digi);
        if (!empty($digi)) {
            $digi  = $digi[0];
            $spell = substr($spell, 0, strlen($spell) - strlen($digi));
        } else {
            $digi = '';
        }
//        var_dump($spell);
//        var_dump($digi);
//        exit();
        $newSpell = '';
        //头尾的分开写，因为操作逻辑比较麻烦
        if ($fuzzy[2] == 'head') {
            //含有1
            if (strpos($spell, $fuzzy[0], 0) === 0) {
                //如果含有2 那说明就是2（长度逻辑），2改成1
                //没有2那就1改成2
                if (strpos($spell, $fuzzy[1], 0) === 0) {
                    $newSpell = $fuzzy[0] . substr($spell, strlen($fuzzy[1]));
                } else {
                    $newSpell = $fuzzy[1] . substr($spell, strlen($fuzzy[0]));
                }
            } else {
                //不含1 如果含有2 2改成1
                if (strpos($spell, $fuzzy[1], 0) === 0) {
                    $newSpell = $fuzzy[0] . substr($spell, strlen($fuzzy[1]));
                }
            }
        } else {
            //含有1
            if (strpos($spell, $fuzzy[0], 0) === (strlen($spell) - strlen($fuzzy[0]))) {
                //如果含有2 那说明就是2（长度逻辑），2改成1
                //没有2那就1改成2
                if (strpos($spell, $fuzzy[1], 0) === (strlen($spell) - strlen($fuzzy[1]))) {
//                    var_dump(__LINE__);
                    $newSpell = substr($spell, 0, strlen($spell) - strlen($fuzzy[1])) . $fuzzy[0];
                } else {
//                    var_dump(__LINE__);
                    $newSpell = substr($spell, 0, strlen($spell) - strlen($fuzzy[0])) . $fuzzy[1];
                }
            } else {
                //不含1 如果含有2 2改成1
                if (strpos($spell, $fuzzy[1], 0) === (strlen($spell) - strlen($fuzzy[1]))) {
//                    var_dump(__LINE__);
                    $newSpell = substr($spell, 0, strlen($spell) - strlen($fuzzy[1])) . $fuzzy[0];
                }
            }
        }
        if (empty($newSpell)) return '';
        return $newSpell . $digi;
    }

}
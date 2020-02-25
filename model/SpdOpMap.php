<?php namespace Model;

class SpdOpMap {
    private static $cache = [

    ];

    /**
     * @param $type string
     * @param $value string
     * @return array
     */
    public static function parseBinary($type, $value) {
        $rows = Settings::get('operate.' . $type);
        if (empty($rows)) return [];

        return self::bin2operate($value, $rows);
    }

    /**
     * @param $type string
     * @param $value array|string
     * @return boolean|int
     */
    public static function writeBinary($type, $value) {
        $rows = Settings::get('operate.' . $type);
//		var_dump($rows);
//		var_dump($type);
        if (empty($rows)) return false;

        return self::operate2bin($value, $rows);
    }

    /**
     * 将操作代码整合到操作
     * @param $input int|string
     * @param $names array
     * @return array
     *
     * [
     *      'delete'=>false
     * ]
     */
    private static function bin2operate($input, $names) {
//		dump('============== bin2operate ============');
        //这里倒置是方便后期添加的，也就是越往下越大……
        $binArray = array_reverse(str_split(decbin($input)));
        //生成数组
        $resultArray = [];
        $len         = sizeof($names);
        for ($i1 = 0; $i1 < $len; $i1++) {
            $resultArray[$names[$i1]] = isset($binArray[$i1]) && (string)$binArray[$i1] == '1';
        }
//		dump($resultArray);
        return $resultArray;
    }

    /**
     * 将操作整合到操作代码
     * 可以自动补齐缺少的部分
     * @param $input array|string
     *
     * array可以为
     *  [
     *       'delete'=>1|false,
     *  ]
     * 或是
     *  [
     *       'delete',
     *  ]
     *
     * @param $names array
     * @return int
     *
     * 输入string的话,匹配单个key
     *
     */
    private static function operate2bin($input, $names) {
//		dump('============ operate2bin ============');
//		dump($input);
        //	dump($names);
        if (is_string($input)) {
            $input = explode(',', $input);
        }
        //如果不是[a,b,c]类型的，就看作[a=>t,b=>f,c=>f,]类型的，然后转成[a,b,c]
        if (empty($input[0])) {
            $input = array_keys(array_filter($input));
        }
        //再翻转回去方便索引
        $input = array_flip($input);
        //拼接字符串
        $targetArray = [];
        foreach ($names as $name) {
            $targetArray[] = isset($input[$name]) ? '1' : '0';
        }
        //倒置，拼接，写入
        $targetString = '0b' . implode('', array_reverse($targetArray));
        //bin
        return bindec($targetString);
    }
}
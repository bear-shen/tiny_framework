<?php namespace Model;

/**
 * @method array|string get($name = '')
 * @method array all()
 */
class Settings extends Kernel {
    private static $_loaded = false;
    private static $_list   = [];

    public function __construct() {
        $data = \Lib\DB::query('select * from spd_config;');
        $this->mergeData($data);
    }

    private function mergeData($dataList) {
        $total = self::$_list;
        foreach ($dataList as $item) {
            $nameChain = explode('.', $item['name']);
            $val       = $item['value'];
            $needle    = &self::$_list;
            foreach ($nameChain as $k) {
                if (empty($needle[$k])) {
                    $needle[$k] = [];
                }
                $needle = &$needle[$k];
            }
            $needle = json_decode($val, true) ?: $val;
        }
        //外部配置项也倒到这边
        global $extraConf;
        if (!empty($extraConf)) {
            self::$_list = $extraConf + self::$_list;
        }
        self::$_loaded = true;
    }

    private static function _all() {
        if (!self::$_loaded) new self();
        return self::$_list;
    }

    private static function _get($key) {
        if (!self::$_loaded) new self();

        $result    = null;
        $needle    = self::$_list;
        $nameChain = explode('.', $key);
        foreach ($nameChain as $k) {
            if (empty($needle[$k])) {
                $needle = null;
                break;
            }
            $needle = $needle[$k];
        }
        if (!empty($needle)) $result = $needle;
        return $result;
    }


    //打laravel抄的
    public static function __callStatic($name, $arguments) {
        $name = '_' . $name;
        return (new self)->$name(...$arguments);
    }

    public function __call($name, $arguments) {
        $name = '_' . $name;
        return $this->$name(...$arguments);
    }

}
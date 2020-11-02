<?php namespace Lib;

use Model\Settings;

/**
 * @method get($key)
 * @method getId()
 * @method set($key, $value)
 * @method save()
 * @method clear()
 */
class Session {
    use FuncCallable;

    private static $init = false;

    private static $sessionId = '';
    private static $_conf     = [];
    private static $_data     = [];

    public function __construct($uuid = false) {
        if (!self::$init) return;
        if (!$uuid) return;
        //
        self::$sessionId = $uuid;
        //
        $sessionConf = (Settings::get('session') ?: []) + [
                'key'    => 'cache:session',
                'prefix' => 'ses:',
                'expire' => 86400 * 180,
            ];
        self::$_conf = $sessionConf;
        //
        global $cache;
        $sessData    = $cache->hget(
            $sessionConf['key'],
            $sessionConf['prefix'] . self::$sessionId
        ) ?: '';
        self::$_data = json_decode($sessData, true) ?: [];
        //
        self::$init = true;
        return;
    }

    public function _get($key) {
        if (!self::$init) return null;
        if (!isset(self::$_data[$key])) return null;
        return self::$_data[$key];
    }

    public function _getId($key) {
        if (!self::$init) return null;
        return self::$sessionId;
    }

    public function _set($key, $value) {
        if (!self::$init) return false;
        self::$_data[$key] = $value;
        return true;
    }

    public function _save() {
        if (!self::$init) return false;
        global $cache;
        $cache->hset(
            self::$_conf['key'],
            self::$_conf['prefix'] . self::$sessionId,
            json_encode(self::$_data, JSON_UNESCAPED_UNICODE)
        );
        return true;
    }

    public function _clear() {
        if (!self::$init) return false;
        global $cache;
        $cache->hdel(
            self::$_conf['key'],
            [self::$_conf['prefix'] . self::$sessionId]
        );
        return true;
    }
}
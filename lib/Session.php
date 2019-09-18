<?php namespace Lib;

/**
 *
 */
class Session {
    use FuncCallable;
    private static $init = false;

    private static $sessionId = '';
    private static $_conf     = [];
    private static $_data     = [];

    public function __construct(Request $request = null) {
        if (self::$init) return;
        if (!$request) return;
        global $conf, $cache;
        self::$_conf = $conf['session'] + [
                'key'    => 'ses_id',
                'prefix' => 'frm:session:',
                'expire' => 86400 * 180,
            ];
        //id
        $cookie    = $request['cookie'];
        $sessionId = '';
        if (empty($cookie[self::$_conf['key']])) {
            $sessionId = md5(microtime(true));
        } else {
            $sessionId = $cookie[self::$_conf['key']];
        }
        self::$sessionId = $sessionId;
        //数据
        $body = $cache->get(
            self::$_conf['prefix'] . self::$sessionId
        );
        if ($body) {
            $body = unserialize($body);
        } else {
            $body = [];
        }
        self::$_data = $body;
        //
        self::$init = true;
        return;
    }

    public function _get($key) {
        if (!isset(self::$_data[$key])) return null;
        return self::$_data[$key];
    }

    public function _getId($key) {
        return self::$sessionId;
    }

    public function _set($key, $value) {
        self::$_data[$key] = $value;
        return true;
    }

    public function _write() {
        global $cache;
        $cache->setex(
            self::$_conf['prefix'] . self::$sessionId,
            self::$_conf['expire'],
            serialize(self::$_data)
        );
        return true;
    }
}
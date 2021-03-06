<?php namespace Lib;


/**
 * @method bool setCookie(array $data)
 * @method bool setSession(string $key,string $value)
 * @method bool setHeader(array|string $data) 'headerdata' | ['string' => '', 'replace' => '', 'code' => '',]
 * @method bool setContent(array|string $data)
 *
 * @property array session
 * @property array cookie
 * @property array header
 * @property string content
 *
 * @method bool execute()
 */
class Response implements \ArrayAccess {
    use FuncCallable;

    private static $_data = [
        /**
         * [
         *  'name'      =>'',
         * ]
         */
        'session' => [],
        /**
         * [
         *  'name'      =>'',
         *  'value'     =>'',
         *  'domain'    =>'',
         *  'path'      =>'',
         *  'expires'   =>'',
         *  'max_age'   =>'',
         *  'http_only' =>'',
         *  'secure'    =>'',
         * ]
         */
        'cookie'  => [],
        /**
         * [
         *  'string'    =>'',
         *  'replace'   =>'',
         *  'code'      =>'',
         * ]
         */
        'header'  => [],
        'content' => '',
    ];

    public function __construct() {
//        parent::__construct();
    }

    /**
     * @see setSession
     */
    private function _setSession($key,$value) {
        Session::set($key,$value);
    }

    private function writeSession() {
        Session::save();
    }

    /**
     * @param $data array|string
     * @return boolean
     * @see setHeader
     */
    private function _setHeader($data) {
        $def = [
            'string'  => '',
            'replace' => true,
            'code'    => null,
        ];
        if (is_string($data)) {
            self::$_data['header'][] =
                [
                    'string' => $data,
                ] + $def;
        } elseif (!empty($data['string'])) {
            self::$_data['header'][] =
                $data + $def;
        } elseif (!is_array($data)) {
            return false;
        } else {
            foreach ($data as $item) {
                if (is_string($item)) {
                    self::$_data['header'][] =
                        [
                            'string' => $item,
                        ] + $def;
                } elseif (!empty($data['string'])) {
                    self::$_data['header'][] =
                        $item + $def;
                }
            }
        }
        return true;
    }

    private function writeHeader() {
        foreach (self::$_data['header'] as $item) {
            header($item['string'], $item['replace'], $item['code']);
        }
        return true;
    }

    /**
     * @see setCookie
     */
    private function _setCookie($data) {
        foreach ($data as $item) {
            if (is_string($item)) {
                $data = [$data];
                break;
            }
            break;
        }
//        var_dump($data);
        foreach ($data as $item) {
            self::$_data['cookie'][] = $item + [
                    'name'      => '',
                    'value'     => '',
                    'domain'    => '',
                    'path'      => '',
                    'expires'   => strtotime('+30 day'),
                    'max_age'   => 86400 * 30,
                    'http_only' => false,
                    'secure'    => false,
                ];
        }
    }

    private function writeCookie() {
        foreach (self::$_data['cookie'] as $item) {
            setcookie(
                $item['name'],
                $item['value'],
                $item['expires'],
                $item['path'],
                $item['domain'],
                $item['secure'],
                $item['http_only']
            );
        }
        return true;
    }

    /**
     * @see setContent
     */
    private function _setContent($data) {
        if (is_string($data)) {
            self::$_data['content'] = $data;
        } else {
            self::$_data['content'] = json_encode($data, JSON_UNESCAPED_UNICODE);
        }
    }

    private function writeContent() {
        echo self::$_data['content'];
        return true;
    }

    /**
     * @see execute
     */
    private function _execute() {
//        var_dump(self::$_data);
        if (Request::method() == 'CLI') {
            $this->writeContent();
        } else {
            $this->writeCookie();
            $this->writeHeader();
            $this->writeContent();
            $this->writeSession();
            //var_dump('session write');
        }
    }


    // ------------------------------------------------------

    public function offsetExists($offset) {
        return isset(self::$_data[$offset]);
    }

    public function offsetGet($offset) {
        if (empty(self::$_data[$offset])) return false;
        return self::$_data[$offset];
    }

    public function offsetSet($offset, $value) {
        if (is_null($offset)) {
            self::$_data[] = $value;
        } else {
            self::$_data[$offset] = $value;
        }
    }

    public function offsetUnset($offset) {
        unset(self::$_data[$offset]);
    }

    // ------------------------------------------------------

    public function __get($name) {
        return self::$_data[$name];
    }

    public function __set($name, $value) {
        self::$_data[$name] = $value;
    }

    public function __isset($name) {
        return !empty(self::$_data[$name]);
    }

    public function __unset($name) {
        self::$_data[$name] = null;
    }

    // ------------------------------------------------------

}
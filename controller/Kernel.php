<?php namespace Controller;

use Lib\Request;

class Kernel {
    public function apiRet($data = [], $code = 0, $msg = 'success') {
        return json_encode(
            [
                'code' => $code,
                'msg'  => $msg,
                'data' => $data,
            ], JSON_UNESCAPED_UNICODE
        );
    }

    public function apiErr($code = 0, $msg = 'success', $data = []) {
        return json_encode(
            [
                'code' => $code,
                'msg'  => $msg,
                'data' => $data,
            ], JSON_UNESCAPED_UNICODE
        );
    }

    /**
     * @param array $params
     * [
     *      string:
     *          'param',
     *      array:
     *          ['param','require|nullable','default']
     *      string with config:
     *          'param:require:default'
     * ]
     * @return array
     */
    public function validate($params = []) {
        $data = Request::data();
        foreach ($params as $param) {
            $paramArr=[];
            if (is_string($param)) {
            } elseif (is_array($param)) {

            }
        }
        return [];
    }
}
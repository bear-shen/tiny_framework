<?php namespace Controller;

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

    public function apiErr($code = 0, $msg = 'success') {
        return json_encode(
            [
                'code' => $code,
                'msg'  => $msg,
            ], JSON_UNESCAPED_UNICODE
        );
    }
}
<?php namespace Controller;

use Lib\Request;
use Lib\Response;
use Lib\Router;

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
     *      'key' => '{required|nullable|default:\S+}|{array|string|json|integer|min:\d+|max:\d+|between:\d+,\d+}'
     *      'key' => '{required|nullable}'
     * ]
     * @return array
     */
    public function validate($params = [], $data = false) {
        $data = $data ? $data : Request::data();
//        var_dump($data);
//        var_dump($params);
        $targetData = [];
        $invalid    = [];
        foreach ($params as $key => $profile) {
            $pf         = explode('|', $profile) + ['required', 'string'];
            $ctA        = explode(':', $pf[0]);
            $tpA        = explode(':', $pf[1]);
            $configured = isset($data[$key]);
//            var_dump($configured);
            if (!$configured) {
                switch ($ctA[0]) {
                    case'required':
                        $invalid[] = [$key, 'is required'];
                        continue 2;
                        break;
                    case'nullable':
                        $targetData[$key] = null;
                        continue 2;
                        break;
                    case'default':
                        $targetData[$key] = isset($ctA[1]) ? $ctA[1] : null;
                        continue 2;
                        break;
                }
                continue;
            }
            $targetData[$key] = $data[$key];
            switch ($tpA[0]) {
                default:
                case 'string':
                    break;
                case 'array':
                    $targetData[$key] = explode(',', $data[$key]);
                    break;
                case 'json':
                    $targetData[$key] = json_decode($data[$key], true);
                    break;
                case 'integer':
                    $targetData[$key] = intval($data[$key]);
                    break;
                //这样转换显然容易出问题，但是这样目前是够用的
                case 'min':
                    if ($tpA[1] > $data[$key]) {
                        $invalid[] = [$key, 'not match'];
                    } else {
                        $targetData[$key] = ($data[$key] * 1) ?: 0;
                    }
                    break;
                case 'max':
                    if ($tpA[1] < $data[$key]) {
                        $invalid[] = [$key, 'not match'];
                    } else {
                        $targetData[$key] = ($data[$key] * 1) ?: 0;
                    }
                    break;
                case 'between':
                    $tf = explode(',', $tpA[1]);
                    if ($tf[0] > $data[$key] || $tf[1] < $data[$key]) {
                        $invalid[] = [$key, 'not match'];
                    } else {
                        $targetData[$key] = ($data[$key] * 1) ?: 0;
                    }
                    break;
            }
        }
        if (!empty($invalid)) {
            $msg = [];
            foreach ($invalid as $inv) {
                $msg[] = implode(' ', $inv);
            }
            $this->responseError(implode(',', $msg), 400);;
        }
        return $targetData;
    }

    private function responseError($msg, $code = 100, $data = []) {
        Response::setContent(
            [
                'code' => $code,
                'msg'  => $msg,
                'data' => $data,
            ]
        );
        Response::execute();
        exit();
    }
}
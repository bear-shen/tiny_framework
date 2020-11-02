<?php namespace Middleware;

use Lib\GenFunc;
use Lib\Request;
use Lib\Response;
use Lib\Session;
use Model\Settings;

class UserAuth implements Base {
    /**
     * @param Request $request
     * @return Request|mixed
     */
    public function handle(Request $request) {
        $curUid = Session::get('uid');
        if (empty($curUid)) return
            [
                'code' => 111,
                'msg'  => 'login required',
                'data' => [],
            ];
        return $request;
    }
}
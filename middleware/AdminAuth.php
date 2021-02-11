<?php namespace Middleware;

use Lib\GenFunc;
use Lib\ORM;
use Lib\Request;
use Lib\Response;
use Lib\Session;
use Model\Settings;

class AdminAuth implements Base {
    /**
     * @param Request $request
     * @return Request|mixed
     */
    public function handle(Request $request) {
        $groupId  = Session::get('id_group');
        $curGroup = ORM::table('user_group')->where('id', $groupId)->first();;
        if (empty($curGroup) || !$curGroup['admin']) return
            [
                'code' => 403,
                'msg'  => 'not permitted',
                'data' => [],
            ];
        return $request;
    }
}
<?php namespace Middleware;

use Lib\GenFunc;
use Lib\Request;
use Lib\Response;
use Lib\Session;
use Model\Settings;

class UseSession implements Base {
    /**
     * @param Request $request
     * @return Request
     */
    public function handle(Request $request) {
        $cookie     = $request::cookie();
        $cookieConf = (Settings::get('cookie') ?: []) + [
                'key'    => 'cookie',
                'expire' => 86400 * 180,
            ];
        //
        $uuid = '';
        if (isset($cookie[$cookieConf['key']])) {
            $uuid = $cookie[$cookieConf['key']];
        } else {
            $uuid = GenFunc::uuid_v4();
            Response::setCookie(
                [
                    'name'      => $cookieConf['key'],
                    'value'     => $uuid,
                    //                'domain'    => '',
                    'path'      => '/',
                    'expires'   => time() + $cookieConf['expire'],
                    'max_age'   => $cookieConf['expire'],
                    'http_only' => false,
                    'secure'    => false,
                ]);
        }
        new Session($uuid);
        return $request;
    }
}
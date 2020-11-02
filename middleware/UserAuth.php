<?php namespace Middleware;

use Lib\GenFunc;
use Lib\Request;
use Lib\Response;
use Model\Settings;

class UserAuth implements Base {
    /**
     * @param Request $request
     * @return Request
     */
    public function handle(Request $request) {
        return $request;
    }
}
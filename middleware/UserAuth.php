<?php namespace Middleware;

use Lib\Request;

class UserAuth implements Base {
    /**
     * @param Request $request
     * @return Request
     */
    public function handle(Request $request) {
        return $request;
    }
}
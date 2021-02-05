<?php namespace Middleware;

use Lib\Request;

Interface Base {
    /**
     * @param Request $request
     * @return Request|array|string
     */
    public function handle(Request $request);
}
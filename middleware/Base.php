<?php namespace Middleware;

use Lib\Request;

Interface Base {
    /**
     * @param Request $request
     * @return array
     */
    public function handle(Request $request);
}
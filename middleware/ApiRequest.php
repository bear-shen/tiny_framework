<?php namespace Middleware;

use Lib\Request;

class ApiRequest implements Base {
    /**
     * @param Request $request
     * @return Request
     */
    public function handle(Request $request) {
//        var_dump('middleware execute');
        $post = file_get_contents('php://input');
        if (empty($post)) return $request;
        $post            = json_decode($post, true);
        $request['data'] = $post;
        return $request;
    }
}
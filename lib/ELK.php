<?php namespace Lib;

use Elasticsearch\ClientBuilder;

class ELK {
    use FuncCallable;

    private $builder = null;

    public function __construct() {
        $this->builder = ClientBuilder::create();
        $this->builder->setBasicAuthentication('elastic', 'elastic');
    }
}
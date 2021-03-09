<?php namespace Lib;

use Elasticsearch\ClientBuilder;

/**
 * @see https://packagist.org/packages/elasticsearch/elasticsearch#v7.11.0
 */
class ELK {
    use FuncCallable;

    private $builder = null;
    private $client  = null;

    public function __construct() {
        $this->client = ClientBuilder::fromConfig(
            [
                'Hosts'               => ['127.0.0.1:9200'],
                'Retries'             => 1,
                'BasicAuthentication' => ['elastic', 'elastic'],
                'ConnectionPool'      => \Elasticsearch\ConnectionPool\SimpleConnectionPool::class,
                'Selector'            => \Elasticsearch\ConnectionPool\Selectors\StickyRoundRobinSelector::class,
                'Serializer'          => \Elasticsearch\Serializers\SmartSerializer::class,
            ]
        );
        /*$this->builder = ClientBuilder::create()->
        setHosts(['127.0.0.1:9200'])->
        setRetries(1)->
        setBasicAuthentication('elastic', 'elastic')->
        setConnectionPool(\Elasticsearch\ConnectionPool\SimpleConnectionPool::class)->
        setSelector(\Elasticsearch\ConnectionPool\Selectors\StickyRoundRobinSelector::class)->
        setSerializer(\Elasticsearch\Serializers\SmartSerializer::class)->
        build();*/
        return $this;
    }

    private function _client() {
        return $this->client;
    }
}
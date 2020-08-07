<?php
/**
 * Created by PhpStorm.
 * User: jamespi
 * Date: 2020/8/4
 * Time: 13:50
 */

namespace Jamespi\GaClinet\Api;

use Elasticsearch\ClientBuilder;
use Elasticsearch\Common\Exceptions\TransportException;
use Elasticsearch\Common\Exceptions\MaxRetriesException;
class ElasticSearchStart
{
    /**
     * 维度参数
     * @var array
     */
    protected $dimension_params = [];
    /**
     * 指标参数
     * @var array
     */
    protected $metric_params = [];
    /**
     *
     * @var array
     */
    protected $dimension_data = [];

    protected $metric_data = [];

    protected $client;

    public function __construct()
    {
        $this->client = ClientBuilder::create();
    }

    /**
     * 设置host
     * @param array $hosts ip/域名
     * @return $this
     */
    public function setHosts(array $hosts){
        $this->client = $this->client->setHosts($hosts);
        return $this;
    }

    /**
     * 重连次数
     * @param int $number 次数
     * @return $this
     */
    public function setRetries(int $number = 2){
        $this->client = $this->client->setRetries($number);
        return $this;
    }

    /**
     * 客户端连接对象
     * @return $this
     */
    public function build(){
        $this->client = $this->client->build();
        return $this;
    }

    /**
     * 创建索引
     * @param array $params 索引参数
     * @return \Exception
     */
    public function addDatabases(array $params = []){
        try{
            $response = $this->client->indices()->create($params);
            return $response;
        }catch (TransportException $e){
            return $e->getPrevious();
        }
    }

    /**
     * 索引文档
     * @param array $params 文档参数
     * $params = [
     *   'index' => 'gc-ga',
     *   'type' => 'gc_ga_event_date_detail',
     *   'body' => [ 'testField' => 'abc']
     * ];
     * @return \Exception
     */
    public function addDocumentation(array $params){
        try{
            $response = $this->client->index($params);
            return $response;
        }catch (TransportException $e){
            return $e->getPrevious();
        }
    }

    public function __call($name, $arguments)
    {
        // TODO: Implement __call() method.
    }
}
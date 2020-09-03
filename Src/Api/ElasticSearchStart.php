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
     * 搜索参数
     * @var array
     */
    protected $params = [
        'index' => 'gc-ga-20200830-xxxx2',
        'type' => '_doc',
        'body' => [
            'query' => [
                'constant_score' => [
                    'filter' => [

                    ]
                ]
            ]
        ],
		'from' => 0,
        'size' => 1000
    ];
    /**
     * 实例化链接
     * @var ClientBuilder
     */
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
     * 判断index是否存在
     * @param array $params
     * @return \Exception
     */
    public function isExists(array $params = []){
        try{
            $response = $this->client->indices()->exists($params);
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
        }catch (TransportException $e){
            return $e->getPrevious();
        }
    }
	
	/**
     * 批量插入多条
     * @param array $params
     * @return \Exception
     */
    public function addDocumentationBulk(array $params){
        try{
            $response = $this->client->bulk($params);
        }catch (TransportException $e){
            return $e->getPrevious();
        }
    }

    /**
     * 搜索Documentation
     * @param array $params
     * @return mixed
     */
    public function searchDocumentation(array $params){
        $res = $this->client->search($params);
        return $res;
    }
	
	/**
     * 获取es搜索结果
     * @param string $index 索引名称
     * @param string $mode 选择搜索类型
     * @param array $params 搜索条件
     * @param string $type 索引类型
     * @param string $score 评分模式
     * @return string
     */
    public function getSearchResult(string $index, string $mode, array $params, $from=0, $size=1000, $type='_doc', $score = 'constant_score'){
        switch ($mode){
            case 'range':
                $this->params['index'] = $index;
                $this->params['type'] = $type;
                $this->params['body']['query'][$score]['filter'] = [
                    'range' => $params
                ];
				$this->params['body']['from'] = $from;
                $this->params['body']['size'] = $size;
                break;
            case 'match':
                $this->params['index'] = $index;
                $this->params['type'] = $type;
                $this->params['body']['query'][$score]['filter'] = [
                    'match' => $params
                ];
				$this->params['body']['from'] = $from;
                $this->params['body']['size'] = $size;
                break;
            case 'term':
                $this->params['index'] = $index;
                $this->params['type'] = $type;
                $this->params['body']['query'][$score]['filter'] = [
                    'term' => $params
                ];
				$this->params['body']['from'] = $from;
                $this->params['body']['size'] = $size;
                break;
        }

        $res = $this->setHosts(ES_HOST)->build()->searchDocumentation($this->params);
        return json_encode(['status'=>'success', 'msg'=>'success', 'data'=>$res]);
    }
	
	/**
     * 聚合搜索
     * @param string $index 索引名称
     * @param string $mode 选择搜索模式
     * @param array $params 搜索条件
     * @param array $aggParams 聚合字段
	 * @param string $aggMode 聚合操作类型
     * @param int $from 搜索条数开始位置
     * @param int $size 搜索条数
     * @param string $type 索引类型
     * @param string $score 评分模式
     * @return string
     */
    public function getAggsResult(string $index, string $mode, array $params, array $aggParams, string $aggMode='term', $from=0, $size=1000, $type='_doc', $score = 'constant_score'){
        switch ($mode){
            case 'range':
                $this->params['index'] = $index;
                $this->params['type'] = $type;
                $this->params['body']['query'][$score]['filter'] = [
                    'range' => $params
                ];
                $this->params['body']['from'] = $from;
                $this->params['body']['size'] = $size;
                break;
            case 'match':
                $this->params['index'] = $index;
                $this->params['type'] = $type;
                $this->params['body']['query'][$score]['filter'] = [
                    'match' => $params
                ];
                $this->params['body']['from'] = $from;
                $this->params['body']['size'] = $size;
                break;
            case 'term':
                $this->params['index'] = $index;
                $this->params['type'] = $type;
                $this->params['body']['query'][$score]['filter'] = [
                    'term' => $params
                ];
                $this->params['body']['from'] = $from;
                $this->params['body']['size'] = $size;
                break;
        }

        if($aggParams){
            foreach ($aggParams as $agg){
                $aggKey = 'my_group_by_'.$agg;
                $this->params['body']['aggs'][$aggKey] = [
                    $aggMode => [
                        'field' => $agg
                    ]
                ];
            }
        }

        $res = $this->setHosts(ES_HOST)->build()->searchDocumentation($this->params);
        return json_encode(['status'=>'success', 'msg'=>'success', 'data'=>$res]);
    }
	
	/**
     * 创建模板
     * @param string $templateName 模板名称
     * @param string $jsonName json文件名称
     * @return string
     */
    public function createMappings($templateName='gc-ga', $jsonName=''){
        $jsonPath = dirname(dirname(dirname(dirname(dirname(__DIR__)))))."/".$jsonName;
        $esip = ES_HOST;
        $url="http://".$esip[0].":9200/_template/".$templateName;
        $data = file_get_contents($jsonPath);

        $info = $this->posturl($url, $data);
        return json_encode(['status'=>'success', 'msg'=>'success', 'data'=>$info]);
    }

    public function __call($name, $arguments)
    {
        // TODO: Implement __call() method.
    }
}
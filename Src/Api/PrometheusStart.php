<?php
/**
 * Created by PhpStorm.
 * User: jamespi
 * Date: 2020/8/7
 * Time: 9:41
 */

namespace Jamespi\GaClinet\Api;


use Prometheus\CollectorRegistry;
use Prometheus\RenderTextFormat;
use Prometheus\Storage\InMemory;
use Jamespi\GaClinet\Api\ElasticSearchStart;

class PrometheusStart
{
    public function __construct()
    {
        \Prometheus\Storage\Redis::setDefaultOptions(
            [
                'host' => '127.0.0.1',
                'port' => 6379,
                'password' => null,
                'timeout' => 0.1, // in seconds
                'read_timeout' => '10', // in seconds
                'persistent_connections' => false
            ]
        );
    }

    /**
     * 添加指标
     */
    public function addMetrics(){
        $registry = \Prometheus\CollectorRegistry::getDefault();

        $counterA = $registry->registerCounter('gc', 'james', 'it increases', ['type']);
        $counterA->incBy(3, ['network']);

// once a metric is registered, it can be retrieved using e.g. getCounter:
        $counterB = $registry->getCounter('gc', 'james');
        $counterB->incBy(2, ['red']);
    }

    /**
     * 获取指标
     */
    public function getMetrics(){
        $registry = \Prometheus\CollectorRegistry::getDefault();

        $renderer = new RenderTextFormat();
        $result = $renderer->render($registry->getMetricFamilySamples());

        header('Content-type: ' . RenderTextFormat::MIME_TYPE);
        echo $result;
    }

    /**
     * 搜索文件
     * @param $index
     * @param array $query
     * @return mixed
     */
    public function getEeasticSearch($index, array $query){
        $es = new ElasticSearchStart();
        $esMode = $es->setHosts(ES_HOST)->build();
        $addData = [
            'query' => [
                'match' => $query
            ]
        ];
        $params = [
            'index' => $index,
            'body' => $addData
        ];

        $res = $esMode->searchDocumentation($params);
        var_dump($res);
        return $res;
    }
}
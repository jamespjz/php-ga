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
                'host' => PROMETHEUS_HOST,
                'port' => PROMETHEUS_PORT,
                'password' => PROMETHEUS_PASSWORD,
                'timeout' => 0.1, // in seconds
                'read_timeout' => '10', // in seconds
                'persistent_connections' => false
            ]
        );
    }

    /**
     * 添加指标
     * @param array $dimensionInfo 维度参数
     * @param array $metricInfo 指标参数
     * @param int $viewId 维度key
     */
    public function addMetrics(array $dimensionInfo, array $metricInfo, array $keys, int $viewId){
        $registry = \Prometheus\CollectorRegistry::getDefault();

        foreach ($metricInfo as $value) {
            foreach ($value as $k1=>$v1){
                //指标转换
                switch (str_ireplace(":", "_", $k1)){
                    case 'ga_pageviews':
                        $metrics = 'ga_page_views';
                        break;
                    case 'ga_newUsers':
                        $metrics = 'ga_new_users';
                        break;
                    case 'ga_sessionDurationBucket':
                        $metrics = 'ga_session_duration_bucket';
                        break;
                    case 'ga_avgPageLoadTime':
                        $metrics = 'ga_avg_page_load_time';
                        break;
                    case 'ga_avgSessionDuration':
                        $metrics = 'ga_avg_sesstion_duration';
                        break;
                    case 'ga_avgTimeOnPage':
                        $metrics = 'ga_avg_time_on_page';
                        break;
                    case 'ga_avgServerResponseTime':
                        $metrics = 'ga_avg_server_response_time';
                        break;
                    case 'ga_avgServerConnectionTime':
                        $metrics = 'ga_avg_server_connection_time';
                        break;
                    case 'ga_avgPageDownloadTime':
                        $metrics = 'ga_avg_page_download_time';
                        break;
                    case 'ga_totalEvents':
                        $metrics = 'ga_total_events';
                        break;
                    default:
                        $metrics = str_ireplace(":", "_", $k1);
                        break;
                }

                //设置标签
                $labels = $this->setLabel($dimensionInfo, $metrics);
                //写入Prometheus
                $labels[0][] = 'view_id';
                $labels[1][] = $viewId;
                $counterA = $registry->getOrRegisterGauge('gc', $metrics, 'it increases', $labels[0]);
                $counterA->set($v1, $labels[1]);
            }
        }
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

        return $res;
    }

    /**
     * 设置标签
     * @param array $dimensionInfo
     * @param string $metrics
     * @return array
     */
    protected function setLabel(array $dimensionInfos, string $metrics){
        $labelData = [];
        $labelKey = [];
        $dimensionInfo = [];
        foreach ($dimensionInfos as $k=>$v){
            foreach ($v as $k1=>$v1){
                $dimensionInfo[$k1] = $v1;
            }
        }
        $label = [
            'ga_users' => [
                'date',
                'domain',
                'page_url',
                'ele_tag',
            ],
            'ga_new_users' => [
                'date',
                'domain',
                'page_url',
                'ele_tag',
                'user_type',
                'browser',
                'browser_version',
                'os',
                'os_version',
                'screen_resolution',
                'screen_color',
                'device_category',
                'country',
                'region',
                'city'
            ],
            'ga_cusomers' => [
                'date',
                'domain',
                'page_url',
                'ele_tag',
            ],
            'ga_page_views' => [
                'date',
                'domain',
                'page_url',
                'ele_tag',
                'user_type',
                'browser',
                'browser_version',
                'os',
                'os_version',
                'screen_resolution',
                'screen_color',
                'device_category',
                'country',
                'region',
                'city'
            ],
            'ga_ips' => [
                'date',
                'domain',
                'page_url',
                'ele_tag',
            ],
            'ga_accounts' => [
                'date',
                'domain',
                'page_url',
                'ele_tag',
            ],
            'ga_sessions' => [
                'date',
                'domain',
                'page_url',
                'ele_tag',
            ],
            'ga_session_duration_bucket' => [
                'date',
                'domain',
                'page_url',
                'ele_tag',
            ],
            'ga_avg_page_load_time' => [
                'date',
                'domain',
                'page_url',
                'ele_tag',
            ],
            'ga_avg_sesstion_duration' => [
                'date',
                'domain',
                'page_url',
                'ele_tag',
            ],
            'ga_avg_time_on_page' => [
                'date',
                'domain',
                'page_url',
            ],
            'ga_avg_server_response_time' => [
                'date',
                'domain',
                'page_url',
            ],
            'ga_avg_server_connection_time' => [
                'date',
                'domain',
                'page_url',
            ],
            'ga_avg_page_download_time' => [
                'date',
                'domain',
                'page_url',
            ],
            'ga_total_events' => [
                'date',
                'domain',
                'page_url',
                'ele_tag',
            ],
        ];
        foreach ($label[$metrics] as $value){
            if ($value == 'date') {
                if (isset($dimensionInfo['ga:date']) & !empty($dimensionInfo['ga:date'])) {
                    $labelKey[] = 'date';
                    $labelData[] = $dimensionInfo['ga:date'];
                }
            }
            if ($value == 'domain') {
                if (isset($dimensionInfo['ga:hostname']) & !empty($dimensionInfo['ga:hostname'])) {
                    $labelKey[] = 'domain';
                    $labelData[] = $dimensionInfo['ga:hostname'];
                }
            }
            if ($value == 'page_url') {
                if (isset($dimensionInfo['ga:pagePath']) & !empty($dimensionInfo['ga:pagePath'])) {
                    $labelKey[] = 'page_url';
                    $labelData[] = $dimensionInfo['ga:pagePath'];
                }
            }
            if ($value == 'ele_tag') {
                if (isset($dimensionInfo['ga:eventLabel']) & !empty($dimensionInfo['ga:eventLabel'])) {
                    $labelKey[] = 'ele_tag';
                    $labelData[] = $dimensionInfo['ga:eventLabel'];
                }
            }
            if ($value == 'browser') {
                if (isset($dimensionInfo['ga:browser']) & !empty($dimensionInfo['ga:browser'])) {
                    $labelKey[] = 'browser';
                    $labelData[] = $dimensionInfo['ga:browser'];
                }
            }
            if ($value == 'os') {
                if (isset($dimensionInfo['ga:operatingSystem']) & !empty($dimensionInfo['ga:operatingSystem'])) {
                    $labelKey[] = 'os';
                    $labelData[] = $dimensionInfo['ga:operatingSystem'];
                }
            }
            if ($value == 'screen_resolution') {
                if (isset($dimensionInfo['ga:screenResolution']) & !empty($dimensionInfo['ga:screenResolution'])) {
                    $labelKey[] = 'screen_resolution';
                    $labelData[] = $dimensionInfo['ga:screenResolution'];
                }
            }
            if ($value == 'country') {
                if (isset($dimensionInfo['ga:country']) & !empty($dimensionInfo['ga:country'])) {
                    $labelKey[] = 'country';
                    $labelData[] = $dimensionInfo['ga:country'];
                }
            }
            if ($value == 'city') {
                if (isset($dimensionInfo['ga:city']) & !empty($dimensionInfo['ga:city'])) {
                    $labelKey[] = 'city';
                    $labelData[] = $dimensionInfo['ga:city'];
                }
            }
        }

        return [$labelKey, $labelData];

    }
}
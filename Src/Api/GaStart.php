<?php
/**
 * Created by PhpStorm.
 * User: jamespi
 * Date: 2020/8/6
 * Time: 18:27
 */

namespace Jamespi\GaClinet\Api;

use Jamespi\GaClinet\Api\ElasticSearchStart;
use Jamespi\GaClinet\Api\PrometheusStart;
use Kafka\Exception;

class GaStart
{
    /**
     * 维度信息对象数组
     * @var
     */
    protected $dimensionData = [];
    /**
     * 指标信息对象数组
     * @var
     */
    protected $metricData = [];
    /**
     * 授权信息
     * @var mixed
     */
    protected $KEY_FILE_LOCATION;
    /**
     * 实例化获取Analytics项目信息对象
     * @var
     */
    protected $analytics;
    /**
     * 查询条件
     * @var string
     */
    protected $searchParams;
    /**
     * es索引配置
     * @var array
     */
    protected $config;
    /**
     * ga获取数据对象
     * @var
     */
    protected $response;

    /**
     * GaStart constructor.
     * @param array $dimensionConfig
     * @param array $metricConfig
     * @param array $searchParams
     * @param array $config;
     */
    public function __construct(array $dimensionConfig, array $metricConfig, array $searchParams, array $config)
    {
        $this->KEY_FILE_LOCATION = dirname(dirname(__DIR__)).'/client_secrets.json';
        $this->searchParams = $searchParams;
        $this->config = $config;
        $this->init($dimensionConfig, $metricConfig);
    }

    /**
     * 初始化数据处理
     * @param array $dimensionConfig
     * @param array $metricConfig
     */
    public function init(array $dimensionConfig, array $metricConfig){
        foreach ($dimensionConfig as $k1 => $v1) {
            $dimensionResponse = $this->getReport($v1);
            $this->dimensionData[] = $dimensionResponse;
        }
        foreach ($metricConfig as $k2 => $v2) {
            $metricResponse = $this->getSession($v2);
            $this->metricData[] = $metricResponse;
        }
    }

    /**
     * 维度信息对象
     * @param string $dimensionInfo 维度
     * @return Google_Service_AnalyticsReporting_Dimension
     */
    private function getReport(string $dimensionInfo){
        $country = new \Google_Service_AnalyticsReporting_Dimension();
        $country->setName($dimensionInfo);

        return $country;
    }

    /**
     * 指标信息对象
     * @param string $metricInfo 指标
     * @return Google_Service_AnalyticsReporting_Metric
     */
    private function getSession(string $metricInfo){
        $sessions = new \Google_Service_AnalyticsReporting_Metric();
        $sessions->setExpression($metricInfo);

        return $sessions;
    }

    /**
     * 实例化获取Analytics项目信息对象
     * @return $this
     */
    public function initializeAnalytics(){
        $client = new \Google_Client();
        $client->setApplicationName("Hello Analytics Reporting");
        $client->setAuthConfig($this->KEY_FILE_LOCATION);
        $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
        $this->analytics = new \Google_Service_AnalyticsReporting($client);

        return $this;
    }

    /**
     * 获取ga项目数据
     * @return mixed
     */
    public function getRequest(){
        if ( !(isset($this->searchParams['viewId'])&&!empty($this->searchParams['viewId'])) ){
            throw new Exception("请选择视图id");
        }
        $viewId = $this->searchParams['viewId'];
        $beforeTime = isset($this->searchParams['beforeTime'])?$this->searchParams['beforeTime']:date("Y-m-d", strtotime('yesterday'));
        $lastTime = isset($this->searchParams['lastTime'])?$this->searchParams['lastTime']:date("Y-m-d");
        $pageSize = isset($this->searchParams['pageSize'])?$this->searchParams['pageSize']:10000;
        $pageToken = isset($this->searchParams['pageToken'])?$this->searchParams['pageToken']:'0';

        // Create the DateRange object.
        $dateRange = new \Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate($beforeTime);
        $dateRange->setEndDate($lastTime);

        // Create the ReportRequest object.
        $request = new \Google_Service_AnalyticsReporting_ReportRequest();
        $request->setViewId($viewId);
        $request->setDateRanges($dateRange);
        $request->setMetrics($this->metricData);
        $request->setDimensions($this->dimensionData);
        $request->setPageSize($pageSize);
        $request->setPageToken($pageToken);

        $body = new \Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests( array( $request) );

        $response = $this->analytics->reports->batchGet( $body );

        return $response;
    }

    /**
     * 处理ga信息并将相关数据写入EslasticSearch与Prometheus
     * @param $reports
     * @param int $type
     */
    public function printResults($reports, $type){
		$GLOBALS['nextPageToken'] = $reports->reports[0]->nextPageToken;
        $index = $this->config['index'];
        for ( $reportIndex = 0; $reportIndex < count( $reports ); $reportIndex++ ) {
			$params1 = [];
            $report = $reports[ $reportIndex ];
            $header = $report->getColumnHeader();
            $dimensionHeaders = $header->getDimensions();
            $metricHeaders = $header->getMetricHeader()->getMetricHeaderEntries();
            $rows = $report->getData()->getRows();

            //es创建索引
			$centerTiltle = 'days';
			$indexParams = $this->searchParams['GaEventTitle']?"-".$this->searchParams['GaEventTitle']:'';
            if(!$type) {
                $es = new ElasticSearchStart();
                $esMode = $es->setHosts(ES_HOST)->build();
                $days = (strtotime($this->searchParams['lastTime']) - strtotime($this->searchParams['beforeTime'])) / (24 * 3600);
                for ($i = 0; $i <= $days; $i++) {
                    $time = strtotime($this->searchParams['beforeTime']) + (24 * 3600 * $i);
					$indexParams = $this->searchParams['GaEventTitle']?"-".$this->searchParams['GaEventTitle']:'';
                    
					$this->config['index'] = $index . $indexParams ."-".$centerTiltle."-" . date("Ymd", $time);
                    foreach ($this->dimensionData as $p){
                        if ($p->name == 'ga:dateHourMinute') {
                            $centerTiltle = 'minute';
                            $this->config['index'] = $index . $indexParams . "-".$centerTiltle."-" . date("Ymd", $time);
                        }elseif ($p->name == 'ga:dateHour'){
                            $centerTiltle = 'hour';
                            $this->config['index'] = $index . $indexParams . "-".$centerTiltle."-" . date("Ymd", $time);
                        }
                    }
					
                    $response = $esMode->isExists(['index'=>$this->config['index']]);
                    if(!$response) {
                        $esMode->addDatabases($this->config);
                    }
                }
            }

            for ( $rowIndex = 0; $rowIndex < count($rows); $rowIndex++) {
                $row = $rows[ $rowIndex ];
                $dimensions = $row->getDimensions();
                $metrics = $row->getMetrics();
                $dimension = [];
                $metric = [];
                for ($i = 0; $i < count($dimensionHeaders) && $i < count($dimensions); $i++) {
                    $dimension[][$dimensionHeaders[$i]] = $dimensions[$i];
                }

                for ($j = 0; $j < count($metrics); $j++) {
                    $values = $metrics[$j]->getValues();
                    for ($k = 0; $k < count($values); $k++) {
                        $entry = $metricHeaders[$k];
                        $metric[][$entry->getName()] = $values[$k];
                    }
                }

                //elastricsearch创建索引文档
				$addData1 = [];
                if ($type){
                    $keys = [];
                    $prometheus = new PrometheusStart();
                    foreach ($this->dimensionData as $value){
                        $keys[] = $value->name;
                    }
                    $prometheus->addMetrics($dimension, $metric, $keys, $this->searchParams['viewId']);
                }else {
                    $addData1 = [];
                    $addData = array_merge($dimension, $metric);
                    foreach ($addData as $k1 => $v1) {
                        foreach ($v1 as $k2 => $v2) {
                            $addData1[$k2] = $v2;
                        }
                    }
					
					$params1['body'][] = [
                        'index' => [
                            '_index' => $index.$indexParams."-".$centerTiltle."-" . date("Ymd", $time),
                            '_type' => '_doc',
                        ]
                    ];
                    $params1['body'][] = $addData1;
                    unset($addData);
					
                }
            }
        }

        $esMode->addDocumentationBulk($params1);
    }

    /**
     * 获取执行结果
     * @param int $type 查询类型（0：es日志  1：Prometheus指标）
     */
    public function getResult(int $type = 0){
        $response = $this->initializeAnalytics()
            ->getRequest();
        try {
            $data = $this->printResults($response, $type);
            return json_encode(['status'=>'success', 'msg'=>'写入成功', 'data'=>$data]);
        }catch (\Exception $e){
            return json_encode(['status'=>'failed', 'msg'=>$e->getMessage()]);
        }
    }
}
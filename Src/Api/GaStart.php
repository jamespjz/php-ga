<?php
/**
 * Created by PhpStorm.
 * User: jamespi
 * Date: 2020/8/6
 * Time: 18:27
 */

namespace Jamespi\GaClinet\Api;

use Jamespi\GaClinet\Api\ElasticSearchStart;
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
     * ga获取数据对象
     * @var
     */
    protected $response;

    /**
     * GaStart constructor.
     * @param array $dimensionConfig
     * @param array $metricConfig
     * @param array $searchParams
     */
    public function __construct(array $dimensionConfig, array $metricConfig, array $searchParams)
    {
        $this->KEY_FILE_LOCATION = require_once dirname(dirname(__DIR__)).'/client_secrets.php';
        $this->init($dimensionConfig, $metricConfig);
    }

    /**
     * 初始化数据处理
     * @param array $dimensionConfig
     * @param array $metricConfig
     */
    public function init(array $dimensionConfig, array $metricConfig){
        foreach ($dimensionConfig as $k1 => $v1) {
            $dimensionResponse = getReport($v1);
            $this->dimensionData[] = $dimensionResponse;
        }
        foreach ($metricConfig as $k2 => $v2) {
            $metricResponse = getSession($v2);
            $this->metricData[] = $metricResponse;
        }
    }

    /**
     * 维度信息对象
     * @param string $dimensionInfo 维度
     * @return Google_Service_AnalyticsReporting_Dimension
     */
    private function getReport(string $dimensionInfo){
        $country = new Google_Service_AnalyticsReporting_Dimension();
        $country->setName($dimensionInfo);

        return $country;
    }

    /**
     * 指标信息对象
     * @param string $metricInfo 指标
     * @return Google_Service_AnalyticsReporting_Metric
     */
    private function getSession(string $metricInfo){
        $sessions = new Google_Service_AnalyticsReporting_Metric();
        $sessions->setExpression($metricInfo);

        return $sessions;
    }

    /**
     * 实例化获取Analytics项目信息对象
     * @return $this
     */
    public function initializeAnalytics(){
        $client = new Google_Client();
        $client->setApplicationName("Hello Analytics Reporting");
        $client->setAuthConfig($this->KEY_FILE_LOCATION);
        $client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
        $this->analytics = new Google_Service_AnalyticsReporting($client);

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
        $viewId = isset($this->searchParams['viewId'])?$this->searchParams['viewId']:'';
        $beforeTime = isset($this->searchParams['beforeTime'])?$this->searchParams['beforeTime']:'1daysAgo';
        $lastTime = isset($this->searchParams['lastTime'])?$this->searchParams['lastTime']:'yesterday';
        $pageSize = isset($this->searchParams['pageSize'])?$this->searchParams['pageSize']:10000;
        $pageToken = isset($this->searchParams['pageToken'])?$this->searchParams['pageToken']:'0';

        // Create the DateRange object.
        $dateRange = new Google_Service_AnalyticsReporting_DateRange();
        $dateRange->setStartDate($beforeTime);
        $dateRange->setEndDate($lastTime);
        // Create the ReportRequest object.
        $request = new Google_Service_AnalyticsReporting_ReportRequest();
        $request->setViewId($viewId);
        $request->setDateRanges($dateRange);
        $request->setMetrics($this->metricData);
        $request->setDimensions($this->dimensionData);
        $request->setPageSize($pageSize);
        $request->setPageToken($pageToken);

        $body = new Google_Service_AnalyticsReporting_GetReportsRequest();
        $body->setReportRequests( array( $request) );

        try{
            $this->response = $this->analytics->reports->batchGet( $body );
        }catch (Exception $e){
            $e->getMessage();
        }
    }

    /**
     * 处理ga信息并将相关数据写入EslasticSearch与Prometheus
     */
    public function printResults(){
        for ( $reportIndex = 0; $reportIndex < count( $this->response ); $reportIndex++ ) {
            $report = $this->response[ $reportIndex ];
            $header = $report->getColumnHeader();
            $dimensionHeaders = $header->getDimensions();
            $metricHeaders = $header->getMetricHeader()->getMetricHeaderEntries();
            $rows = $report->getData()->getRows();

            //es创建索引
            $es = new ElasticSearchStart();
            $es->setHosts(['localhost'])->build()->addDatabases();

            for ( $rowIndex = 0; $rowIndex < count($rows); $rowIndex++) {
                $row = $rows[ $rowIndex ];
                $dimensions = $row->getDimensions();
                $metrics = $row->getMetrics();
                $dimension = [];
                $metric = [];
                for ($i = 0; $i < count($dimensionHeaders) && $i < count($dimensions); $i++) {
                    $dimension['key'] = $dimensionHeaders[$i];
                    $dimension['value'] = $dimensions[$i];
                }

                for ($j = 0; $j < count($metrics); $j++) {
                    $values = $metrics[$j]->getValues();
                    for ($k = 0; $k < count($values); $k++) {
                        $entry = $metricHeaders[$k];
                        $metric['key'] = $entry->getName();
                        $metric['value'] = $values[$k];
                    }
                }

                //elastricsearch


            }
        }
    }

}
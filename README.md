GA-Prometheus-Elasticsearch-php
=================
此客户端集合Google Analytics、Prometheus、redis、ElasticSearch功能使用的客户端。<br>

客户端支持功能：
--------------------
 - 根据查询条件对GA中各种维度指标组合进行跨表查询获取数据
 - 支持分页及每页数据条数查询
 - 创建索引（查询条件起始日期作为后缀）
 - 查询所有索引信息 
 - 删除索引
 - 查看文档所有信息
 - 创建索引文档
 - 删除索引文档<br>
 
使用教程说明：
----------------
客户端使用PHP语言编写，采用服务应用形式对GA进行请求调用，获取到数据或客户端会自动将全量数据写入到ElasticSearch中。<br>
开发者依据如下示例代码复制到脚本中进行调用获取ga数据并将数据写入到对于的ElasticSearch与Prometheus中。<br>
当然开发者也可以进行定时任务进行自动化执行。
 - client_secrets.json文本为GA项目鉴权及请求私有参数信息。

使用示例：
````
require_once 'vendor/autoload.php';
use Jamespi\GaClinet\Start;

#es的type(表名名称自己定义)
define("GC_GA_EVENT_TITLE", 'gc_ga_title');
#ES的host
define("ES_HOST", ["192.168.86.196"]);

//GA维度参数
$dimension_params = [
    'ga:date',
    'ga:hostname',
];
//GA指标参数
$metric_params = [
    'ga:sessions',
    'ga:pageviews',
    'ga:users',
    'ga:avgSessionDuration',
    'ga:avgPageLoadTime',
    'ga:avgSessionDuration',
    'ga:avgTimeOnPage',
    'ga:newUsers',
];
//GA查询条件
$searchParams = [
    'viewId' => '206702467',
    'beforeTime' => '2020-08-18',//(Y-m-d)
    'lastTime' => '2020-08-18',
    'pageSize' => 1000,
    'pageToken' => '0'
];

//采集ga数据写入到es调用
echo (new Start())->run($dimension_params, $metric_params, $searchParams)->getResult();

````


Prometheus
--------------
指标数据写入prometheus：
```
require_once 'vendor/autoload.php';
use Jamespi\GaClinet\Start;

# 若不需要使用redis存储可以不用定义如下常量
define("PROMETHEUS_HOST", "dev.redis.service.consul");
define("PROMETHEUS_PORT", 63100);
define("PROMETHEUS_PASSWORD", "tY7cRu9HG_jyDw2r");

//GA维度参数
$dimension_params = [
    'ga:date',
    'ga:hostname',
];
//GA指标参数
$metric_params = [
    'ga:sessions',
    'ga:pageviews',
    'ga:users',
    'ga:avgSessionDuration',
    'ga:avgPageLoadTime',
    'ga:avgSessionDuration',
    'ga:avgTimeOnPage',
    'ga:newUsers',
];
//GA查询条件
$searchParams = [
    'viewId' => '206702467',
    'beforeTime' => '2020-08-18',//(Y-m-d)
    'lastTime' => '2020-08-18',
    'pageSize' => 1000,
    'pageToken' => '0'
];
//获取Prometheus数据格式指标数据
echo (new Start())->run($dimension_params, $metric_params, $searchParams, 3)->getMetrics();
//采集ga指标数据写入到Prometheus
//echo (new Start())->run($dimension_params, $metric_params, $searchParams)->getResult(1);
```


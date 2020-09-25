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
$mode = (new Start());
while(true){
	if($GLOBALS['nextPageToken']){
		$searchParams['pageToken'] = (string)$GLOBALS['nextPageToken'];
	}else{
		if($GLOBALS['nextPageToken'] === NULL){
			break;
		}
	}
	echo $mode->run($dimension_params, $metric_params, $searchParams)->getResult();
}

````

- es搜索调用教程
 ````
 /**
  * 搜索es数据
  * $where数据类型为数组 为搜索条件
  *
  * getSearchResult方法参数1：es索引名称；参数2：搜索模式；参数3：搜索条件；参数4：搜索条数开始位置（默认0）；参数5：搜索条数（默认1000）；参数6：索引类型（默认_doc）；参数7：评分模式(默认constant_score)
  * 搜索模式有：range、match、term
  * 其中range模式为范围搜索，如示例一；match模式为匹配搜索试用全文搜索场景，如示例二；term模式为精确值查询，如示例三
  */
  //范围搜索
  $where = [
      'ga:dateHourMinute' => [
          'gte' => 202008300009,
          'lt' => 202008300010,
      ]
  ];
  /**
   * match/term搜索
   * $where = [
   *  'ga:dateHourMinute' => 202008300009
   * ];
   */
   // 聚合字段集合
  $aggs = [
	'ga:totalEvents',
	'ga:pageviews'
  ];
  /*
   * 通配符搜索
   * 目前只支持query_string正则查询
   */
   $where1 = [
		"analyze_wildcard" => true,
		"default_field" => "ga:pagePath",
		"query" => "/[\\/]order[\\/]order-list.*/"  /**** 搜索/order/order-list开始的url格式 ****/
	];
  
  $aggMode = 'terms';//选择这些类型：terms、stats、count、max、min、avg（stats包含count、max、min、avg）
  $from = 0;
  $size = 1000;
  //搜索
  echo (new Start())->run($dimension_params, $metric_params, $searchParams, 2)->getSearchResult('gc-ga-20200830-xxxx2', 'range', $where, $from, $size);
  /**** query_string正则搜索 echo (new Start())->run($dimension_params, $metric_params, $searchParams, 2)->getSearchResult('gc-ga-user-minute-detail-20200924', 'query_string', $where1, $from, $size); ****/
  //创建索引模板
  echo (new Start())->run($dimension_params, $metric_params, $searchParams, 2)->createMappings('gc-ga-test', 'es_template.json');
  //聚合搜索
  echo (new Start())->run($dimension_params, $metric_params, $searchParams, 2)->getAggsResult('gc-ga-minute-20200830-pppp01', 'term', $where, $aggs, $from, $size);
  
  #示例一(range范围查找)
  $params = [
      'index' => 'gc-ga-20200830-xxxx2',
      'type' => '_doc',
      'body' => [
          'query' => [
              'constant_score' => [
                  'filter' => [
                      'range' => [
                          'ga:dateHourMinute' => [
                              'gte' => 202008300009,
                              'lt' => 202008300010,
                          ]
                      ]
                  ]
              ]
          ]
      ]
  ];
  #示例二(match匹配查询)
  $params = [
      'index' => 'gc-ga-20200830-xxxx2',
      'type' => '_doc',
      'body' => [
          'query' => [
              'constant_score' => [
                  'filter' => [
                      'match' => [
                          'ga:dateHourMinute' => 202008300009
                      ]
                  ]
              ]
          ]
      ]
  ];
  #示例三 (term精确值查询)
  $params = [
      'index' => 'gc-ga-20200830-xxxx2',
      'type' => '_doc',
      'body' => [
          'query' => [
              'constant_score' => [
                  'filter' => [
                      'term' => [
                          'ga:dateHourMinute' => 202008300009
                      ]
                  ]
              ]
          ]
      ]
  ];
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

//采集ga数据写入到es调用
$mode = (new Start());
while(true){
	if($GLOBALS['nextPageToken']){
		$searchParams['pageToken'] = (string)$GLOBALS['nextPageToken'];
	}else{
		if($GLOBALS['nextPageToken'] === NULL){
			break;
		}
	}
	
	//获取Prometheus数据格式指标数据
	echo (new Start())->run($dimension_params, $metric_params, $searchParams, 3)->getMetrics();
	//采集ga指标数据写入到Prometheus
	//echo (new Start())->run($dimension_params, $metric_params, $searchParams)->getResult(1);
}

```


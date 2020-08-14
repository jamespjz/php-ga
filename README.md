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

//设置ES服务器ip或域名（请按该常量名称命名）
define("ES_HOST", ["localhost"]);
//GA维度参数
$dimension_params = [
    'ga:date',
    'ga:dimension1',
    'ga:hostname',
    'ga:pagePath',
    'ga:dimension3',
    'ga:eventAction',
    'ga:eventLabel',
];
//GA指标参数
$metric_params = [
    'ga:totalEvents',
    'ga:pageviews',
    'ga:users',
];
//GA查询条件
$searchParams = [
    'viewId' => '206702467',
    'beforeTime' => '1daysAgo',
    'lastTime' => 'yesterday',
    'pageSize' => 10000,
    'pageToken' => '0'
];

(new Start())->run($dimension_params, $metric_params, $searchParams)->getResult();

````


Prometheus
--------------
一个简单的计数器：
```php
\Prometheus\CollectorRegistry::getDefault()
    ->getOrRegisterCounter('', 'some_quick_counter', 'just a quick measurement')
    ->inc();
```

编写一些增强的指标(metrics):
```php
$registry = \Prometheus\CollectorRegistry::getDefault();

$counter = $registry->getOrRegisterCounter('test', 'some_counter', 'it increases', ['type']);
$counter->incBy(3, ['blue']);

$gauge = $registry->getOrRegisterGauge('test', 'some_gauge', 'it sets', ['type']);
$gauge->set(2.5, ['blue']);

$histogram = $registry->getOrRegisterHistogram('test', 'some_histogram', 'it observes', ['type'], [0.1, 1, 2, 3.5, 4, 5, 6, 7, 8, 9]);
$histogram->observe(3.5, ['blue']);
```

手动注册和检索指标（这些步骤结合在`getOrRegister...`方法中）
```php
$registry = \Prometheus\CollectorRegistry::getDefault();

$counterA = $registry->registerCounter('test', 'some_counter', 'it increases', ['type']);
$counterA->incBy(3, ['blue']);

// once a metric is registered, it can be retrieved using e.g. getCounter:
$counterB = $registry->getCounter('test', 'some_counter')
$counterB->incBy(2, ['red']);
```

公开指标(metrics):
```php
$registry = \Prometheus\CollectorRegistry::getDefault();

$renderer = new RenderTextFormat();
$result = $renderer->render($registry->getMetricFamilySamples());

header('Content-type: ' . RenderTextFormat::MIME_TYPE);
echo $result;
```

更改Redis选项（该示例显示默认值）:
```php
\Prometheus\Storage\Redis::setDefaultOptions(
    [
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => null,
        'timeout' => 0.1, // in seconds
        'read_timeout' => 10, // in seconds
        'persistent_connections' => false
    ]
);
```

使用InMemory存储:
```php
$registry = new CollectorRegistry(new InMemory());

$counter = $registry->registerCounter('test', 'some_counter', 'it increases', ['type']);
$counter->incBy(3, ['blue']);

$renderer = new RenderTextFormat();
$result = $renderer->render($registry->getMetricFamilySamples());
```

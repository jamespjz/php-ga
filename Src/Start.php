<?php
/**
 * Created by PhpStorm.
 * User: jamespi
 * Date: 2020/8/6
 * Time: 18:27
 */

namespace Jamespi\GaClinet;

use ReflectionClass;
use Jamespi\GaClinet\Api\GaStart;
use Jamespi\GaClinet\Api\ElasticSearchStart;
use Jamespi\GaClinet\Api\PrometheusStart;
class Start
{
    /**
     * 服务配置项
     * @var mixed
     */
    protected static $config = [];
    /**
     * 服务场景类型
     * @var int
     * 1：调用ga服务
     * 2：调用elasticSearch服务
     * 3：调用Prometheus服务
     */
    protected $type = 1;
    /**
     * 服务实例化对象
     * @var object
     */
    protected $model;

    public function __construct()
    {
        self::$config = require_once dirname(__DIR__).'/Config/Config.php';
    }

    /**
     * 启动服务
     * @param int $type 服务类型
     * @param array $dimensionConfig 维度配置
     * @param array $metricConfig 指标配置
     * @param array $searchParams 查询条件
     * @return $this
     */
    public function run(array $dimensionConfig, array $metricConfig, array $searchParams, int $type)
    {
        $this->type = $type;
        switch ($type){
            case 1:
                $this->model = (new GaStart($dimensionConfig, $metricConfig, $searchParams));
                break;
            case 2:
                $this->model = (new ElasticSearchStart());
                break;
            case 3:
                $this->model = (new PrometheusStart());
                break;
            default:
                $this->model = (new GaStart());
                break;
        }

        return $this;
    }

    public function __call(string $name, array $arguments)
    {
        // TODO: Implement __call() method.
        if ($this->type == 1)
            $server = new ProducerServer();
        else
            $server = new ConsumerServer();
        try{
            $class = new ReflectionClass($this->model);
            $class->getMethod($name);
            $data = call_user_func_array([$this->model, $name], [$server, $arguments[0]]);
            $data = json_decode($data, true);
            if ($data['status'] == 'success')
                return json_encode(['status'=>'success', 'msg'=>'调用成功！', 'data'=>isset($data['data'])?$data['data']:[]]);
            else
                return json_encode(['status'=> 'failed', 'msg'=>'Error：'.isset($data['msg'])?$data['msg']:[], 'data'=>isset($data['data'])?$data['data']:[]]);
        }catch (\Exception $e){
            return json_encode(['status'=> 'failed', 'msg'=>'Error：'.$e->getMessage()]);
        }
    }
}
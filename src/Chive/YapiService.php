<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Chive\Chive;

use Chive\Annotation\Form\ApiDefinition;
use Chive\Annotation\Form\ApiDefinitions;
use Chive\Annotation\Form\ApiResponse;
use Chive\Annotation\Form\Body;
use Chive\Annotation\Form\FormData;
use Chive\Annotation\Form\Param;
use Chive\Annotation\Form\Query;
use Chive\Annotation\Route\ClassRoute;
use Chive\Annotation\Route\MethodRoute;
use Chive\Chive\RoutesService;
use Chive\Exception\BusinessException;
use Chive\Helper\AnnotationHelper;
use Chive\Helper\CommonHelper;
use Chive\Helper\ErrorHelper;
use Chive\Helper\HttpHelper;
use Chive\Helper\RouteHelper;
use Closure;
use Doctrine\Common\Annotations\AnnotationReader;
use Hyperf\Command\Command;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\HttpServer\Router\DispatcherFactory;
use Hyperf\HttpServer\Router\Handler;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Arr;
use Symfony\Component\Console\Input\InputOption;


class YapiService
{
    public $config;
    /** @var array 存总的swagger.json文件 */
    public $swagger;

    public $logger;

    private static $opt;

    public $server = 'http';

    //配置key列表
    const CONFIG_LIST = [
        'domain_name',
        'token',
        'project_id',
        'mode',
    ];

    const REQUEST_URL_IMPORT_DATA = '/api/open/import_data'; //服务端数据导入
    const REQUEST_URL_FUN_LIST = '/api/interface/list'; //接口列表
    const SUCCESS_CODE = 0; //第三方接口请求成功码

    public function __construct()
    {
        $container    = ApplicationContext::getContainer();
        $config       = $container->get(ConfigInterface::class);
        $this->yapi = $config->get('chive.yapi', []);
        $this->checkConfig();
        $this->logger  = make(StdoutLoggerInterface::class);
        $this->swagger = $config->get('chive.swagger');
    }

    public function mainNoAuth()
    {

        //先更新接口文件
        make(SwaggerBranchService::class)->mainNoAuth();
        $path = config('chive.swagger_output_path');
        $this->logger->info("生成swagger.json完成!【{$path}】");

        $file_path = BASE_PATH . "/" . $path . "App_Controller_" . ucfirst(str_replace("Controller","",$this->getOptions('controller'))) . "Controller.json";
        $str = '';
        if (file_exists($file_path)) {
            $str = file_get_contents($file_path); //将整个文件内容读入到一个字符串中
        }
        if (empty($str)) {
            $this->logger->error('获取接口文件数据失败，请确认当前页面有需要上传的接口注解：file_path：' . $file_path);
            return;
        }
        $this->logger->info($file_path.'文件读取成功');


        //组装获取远程服务的api文档地址
        $get_fun_list = $this->getFunList();
        $detail_id = '';
        $route_rule_split = config('chive.route_rule_split');
        $controller_route = '/'.lcfirst(str_replace("Controller","",$this->getOptions('controller')));
        $route = $controller_route.$route_rule_split.$this->getOptions('function');
        if(!empty(self::getOptions('function'))){
            $swagger_data = json_decode($str,true);
            $function_api = $swagger_data['paths'][$route];
            if(empty($function_api)){
                $this->logger->error('找不对应方法名的api文档：' . $file_path);
                return;
            }
            $swagger_data['paths'] = [$route=>$function_api];
            $str = json_encode($swagger_data,JSON_UNESCAPED_UNICODE);
            if(isset($function_api)){
                $detail_id = array_column($get_fun_list,null,'path')[$route]['_id'];
            }
        }else{
            $search_list = CommonHelper::searchArr($get_fun_list,$controller_route.$route_rule_split,'path');
            $search_detail = array_shift($search_list);
            if(isset($search_detail) && isset($search_detail['catid'])){
                $detail_id = 'cat_'.$search_detail['catid'];
            }
        }

        //上传数据到远程服务器
        $this->uploadApi($params = ['data_str' => $str]);

        return sprintf("%s/project/%s/interface/api/%s",$this->yapi['domain_name'],$this->yapi['project_id'],$detail_id);

    }


    /**
     * 上传api接口数据
     */
    public function uploadApi(array $params)
    {

        $this->logger->info('开始请求接口上传');

        $send_data = [
            'type'  => 'swagger',
            'json'  => $params['data_str'],
            'merge' => $this->yapi['mode'],
            'token' => $this->yapi['token'],
        ];
        $element   = make(HttpHelper::class)->post($this->getUrl(self::REQUEST_URL_IMPORT_DATA), [
            'headers'     => ['Content-Type' => 'application/x-www-form-urlencoded;charset=UTF-8'],
            'form_params' => $send_data,
        ], true);

        $res = (string)$element->getBody();
        $this->logger->info('结束请求接口上传,平台返回数据' . $res);

        return json_decode($res, true);
    }

    /**
     * 获取yapi接口列表
     */
    public function getFunList()
    {

        $send_data = [
            'project_id' => $this->yapi['project_id'],
            'token'      => $this->yapi['token'],
            'page'       => 1,
            'limit'      => 99999,
        ];

        $element = make(HttpHelper::class)->get($this->getUrl(self::REQUEST_URL_FUN_LIST), [
            'headers' => ['Content-Type' => 'application/json;charset=UTF-8'],
            'query'   => $send_data,
        ], true);

        $res = (string)$element->getBody();

        $result = json_decode($res, true);

        if (isset($result['errcode']) && $result['errcode'] == self::SUCCESS_CODE) {
            return $result['data']['list']??[];
        } else {
            throw new BusinessException(ErrorHelper::FAIL_CODE, '获取接口列表数据失败，第三方接口返回参数：' . $res);
        }
    }

    /**
     * 获取完整请求路由
     * @param string $route
     */
    public function getUrl(string $route)
    {
        return $this->yapi['domain_name'] . $route;
    }


    /**
     * 检查配置参数
     */
    public function checkConfig()
    {
        $fail_list = [];
        foreach (self::CONFIG_LIST as $config_name) {
            if (empty($this->yapi[$config_name])) {
                $fail_list[] = $config_name;
            }
        }
        if (!empty($fail_list)) {
            throw new BusinessException(ErrorHelper::FAIL_CODE, '配置值：' . implode(',', $fail_list) . ' 必填');
        }
    }

    /**
     * 保存命令传参
     * @param array $opt
     */
    final static public function setOptions(array $opt)
    {
        self::$opt = $opt;
    }

    /**
     * 获取命令传参
     * @param array $opt
     */
    public static function getOptions(string $key = '')
    {
        return empty($key) ? self::$opt : (self::$opt[$key] ?? '');
    }

}

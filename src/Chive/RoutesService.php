<?php
/**
 * Class RoutesService
 * 作者: su
 * 时间: 2021/4/19 15:27
 * 备注: 路由生成处理方法
 */

namespace Chive\Chive;

use Chive\Annotation\Route\ClassRoute;
use Chive\Annotation\Route\MethodRoute;
use Chive\Annotation\Route\NoRoute;
use Chive\Annotation\Route\WebsocketRoute;
use Chive\Helper\AnnotationHelper;
use Doctrine\Common\Annotations\AnnotationReader;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Di\ReflectionManager;
use Psr\Log\LogLevel;

class RoutesService
{
	/**
	 * @var string 默认的路由方法["post"|"get"|"post,get"]
	 */
	protected $method = '';

	/**
	 * @var string 生成路由分隔符，如："/"生成/demo/index。"_"生成/demo_index路由。
	 */
	protected $split = '';

	/** @var array 忽略生成的方法 */
	protected $ignore = [];

	/** @var array 生成路由需要忽略掉的方法 */
	static $ignoreMehtod = [
		'__construct',
		'success',
		'fail',
		'__proxyCall',
		'__getParamsMap',
		'handleAround',
		'makePipeline',
		'getClassesAspects',
		'getAnnotationAspects',
		'__handlePropertyHandler',
		'__handle',
		'__handleTrait',
	];

	/**
	 * @var array 存放路由信息
	 */
	public $routes = [];

	public function __construct()
	{
		$this->method = explode(",", config('chive.route_method', 'post,get'));
		$this->split  = config('chive.route_rule_split', '/');
		$ignore       = config('chive.route_ignore',[]);
		$this->ignore = array_merge(self::$ignoreMehtod, $ignore);
	}

	public function main()
	{
		$enable = config('chive.route_auto_create_enable');
		if ($enable != true) {
			return;
		}
		$this->mainNoAuth();
	}

	public function mainNoAuth()
	{
		$routeArr     = $this->readClassRoute();
		$httpStr      = $this->createHttpRouteFileString($routeArr);
		$websocketStr = $this->createWebsocketRouteFileString();
		$extraStr     = $this->getExtra();

		$log        = make(StdoutLoggerInterface::class);
		$createPath = config('chive.route_create_path');
		if (empty($createPath)) {
			$log->error('路由保存路径为空，无法生成路由规则!');
			return;
		}
		file_put_contents($createPath, $httpStr . $websocketStr . $extraStr);
		$log->info('chive自动生成路由完成!');
	}

	/**
	 * 解析类路由
	 */
	public function readClassRoute()
	{
		$classList = AnnotationCollector::getClassesByAnnotation(ClassRoute::class);
		ksort($classList);

		$routeArr       = [];
		$middlewarePath = config('devtool.generator.middleware.namespace', 'App\\Middleware');
		/**
		 * @var string     $className
		 * @var ClassRoute $classRoute
		 */
		foreach ($classList as $className => $classRoute) {
			$classRoutes = AnnotationHelper::classMetadata($className);        // 获取配置了多个路由的情况

			$obj     = new \ReflectionClass($className);
			$methods = $obj->getMethods();

			// 2021-10-29。判断类注释是否含NoRoute，含有跳过生成路由
            $isBreak = false;
            foreach ($classRoutes as $classRoute) {
                if($classRoute instanceof NoRoute) {
                    $isBreak = true;
                    break;
                }
            }
            if($isBreak) {
                continue;
            }

			// 默认为http协议
			$server = 'http';
			foreach ($classRoutes as $classRoute) {
				if (!($classRoute instanceof ClassRoute)) {
					continue;
				}
				if (!empty($classRoute->server)) {
					$server = $classRoute->server;
				}
				if (!isset($routeArr[$server])) {
					$routeArr[$server] = [];
				}

				// 路径
				$prefix = '';
				if (!empty($classRoute->prefix)) {
					$prefix = $classRoute->prefix;
				} else {
					$classArr = explode("\\", $className);
					if ($classArr[0] != 'App' && $classArr[1] != 'Controller') {
						$this->line('不能生成app\Controller\目录外的路由：' . $className, 'error');
						continue;
					}
					$pathArr = [];
					foreach ($classArr as $pathName) {
						if (in_array($pathName, ['App', 'Controller'])) {
							continue;
						}
						$pathName = lcfirst($pathName);
						if (strlen($pathName) == 2) {
							$pathName = strtolower($pathName);
						}
						if (strpos($pathName, 'Controller') !== false) {
							$pathName = substr($pathName, 0, strlen($pathName) - 10);
						}
						$pathArr[] = $pathName;
					}
					$prefix = '/' . implode($this->split, $pathArr);
				}
				if ($prefix != '/') {
					$prefix = $prefix . $this->split;
				}

				// 请求方法, 默认只有POST
				if (empty($classRoute->methods)) {
					$method = $this->method;
				} else {
					$method = explode(",", $classRoute->methods);
				}
				foreach ($method as &$m) {
					$m = strtoupper($m);
				}

				// 中间件
				$classMiddlewareArr = [];
				if (!empty($classRoute->middleware)) {
					$classMiddlewares = explode(",", $classRoute->middleware);
					foreach ($classMiddlewares as $classMiddleware) {
						if (strpos($classMiddleware, $middlewarePath) === false) {
							$classMiddlewareArr[] = $middlewarePath . '\\' . $classMiddleware;
						} else {
							$classMiddlewareArr[] = $classMiddleware;
						}
					}
				}

				$function = [];
				/** @var \stdClass $stdClass */
				foreach ($methods as $stdClass) {
					if (in_array($stdClass->name, $this->ignore)) {
						continue;
					}
					$funcName            = $stdClass->name;
					$methodMiddlewareArr = [];
					$funcMethod          = $method;
					$isAddMethod         = true;        // 是否把方法添加都路由中
					// 读取方法注解
					$methodsAnnotations = AnnotationHelper::methodMetadata($className, $funcName);
					/** @var MethodRoute $annotation */
					foreach ($methodsAnnotations ?? [] as $annotation) {
						if ($annotation instanceof NoRoute) {
							$isAddMethod = false;
							break;
						}
						if ($annotation instanceof MethodRoute && $annotation->server == $classRoute->server) {
							if (!empty($annotation->path)) {
								$funcName = $annotation->path;
							}
							if (!empty($annotation->middleware)) {
								$methodMiddlewares = explode(",", $annotation->middleware);
								foreach ($methodMiddlewares as $methodMiddleware) {
									if (strpos($methodMiddleware, $middlewarePath) === false) {
										$methodMiddlewareArr[] = $middlewarePath . '\\' . $methodMiddleware;
									} else {
										$methodMiddlewareArr = $methodMiddleware;
									}
								}
							}
							if (!empty($annotation->methods)) {
								$funcMethod = explode(",", $annotation->methods);
								foreach ($funcMethod as &$m) {
									$m = strtoupper($m);
								}
							}
							break;
						}
					}
					if($isAddMethod == false) {
						continue;
					}
					$function[] = [
						'funcName'   => $funcName,          // 访问路由名
						'method'     => $funcMethod,        // 可访问方法
						'controller' => $className,
						'func'       => $stdClass->name,    // 函数名
						'middleware' => $methodMiddlewareArr,  // 当前方法使用的中间件
					];
				}

				if (empty($function)) {
					continue;
				}
				$routeArr[$server][] = [
					'controller' => $className,
					'prefix'     => $prefix,
					'function'   => $function,
					'middleware' => $classMiddlewareArr,       // 当前group用的中间件
				];
			}
		}

		$this->routes = $routeArr;
		return $routeArr;
	}

	/**
	 * 获取指定的类方法注解
	 * @param $className
	 * @param $methodName
	 * @return array
	 */
	public static function methodMetadata($className, $methodName)
	{
		$reflectMethod = ReflectionManager::reflectMethod($className, $methodName);
		$reader        = new AnnotationReader();
		return $reader->getMethodAnnotations($reflectMethod);
	}

	/**
	 * 创建路由文件
	 * @param $routeArr
	 */
	public function createHttpRouteFileString($routeArr)
	{
		$header = "<?php
declare(strict_types=1);

/**
 * 路由文件自动生成，请勿手动修改
 * 生成方法：
 * 1.在controller中写注解ClassRoute/MethodRoute
 * 2.运行 php bin/hyperf.php chive:route 生成路由文件
 */
 
use Hyperf\HttpServer\Router\Router;

Router::addRoute(['GET', 'POST', 'HEAD'], '/', 'App\Controller\IndexController@index');
Router::get('/favicon.ico', function () {
    return '';
});
Router::get('/info', function () {
    return [
        'APP_NAME'    => env('APP_NAME'),
        'APP_ENV'     => env('APP_ENV'),
        'SERVER_DATE' => date('Y-m-d H:i:s', time()),
        'SERVER_TIME' => time(),
    ];
});

";
		$str    = '';
		foreach ($routeArr as $server => $groupList) {
			$t = '';
//			if ($server != 'http') {
			$str .= "Router::addServer('{$server}', function () {" . PHP_EOL;
			$t   = "\t";
//			}
			foreach ($groupList as $group) {
				$str .= $t . "Router::addGroup('{$group['prefix']}', function () {" . PHP_EOL;
				foreach ($group['function'] as $route) {
					$str .= $t . "\t" . "Router::addRoute([";
					foreach ($route['method'] as &$method) {
						$method = "'" . $method . "'";
					}
					$str .= implode(",", $route['method']);
					$str .= "], '{$route['funcName']}', [{$route['controller']}::class, '{$route['func']}']";
					if (!empty($route['middleware'])) {
						$str .= "," . PHP_EOL . "\t\t['middleware' => [";
						foreach ($route['middleware'] as $middleware) {
							$str .= $middleware . "::class, ";
						}
						$str .= "]]";
					}
					$str .= ");" . PHP_EOL;
				}
				if (!empty($group['middleware'])) {
					$str .= $t . "}, ['middleware' => [";
					foreach ($group['middleware'] as $middleware) {
						$str .= $middleware . "::class, ";
					}
					$str .= "]]);" . PHP_EOL;
				} else {
					$str .= $t . "});" . PHP_EOL;
				}
//				if ($server == 'http') {
//					$str .= PHP_EOL;
//				}
			}
//			if ($server != 'http') {
			$str .= "});" . PHP_EOL;
//			}
		}

		return $header . $str . PHP_EOL;
	}

	/**
	 * 读websocket路由配置
	 */
	public function createWebsocketRouteFileString()
	{
		$classList = AnnotationCollector::getClassesByAnnotation(WebsocketRoute::class);
		if (empty($classList)) {
			return '';
		}
		$str = '';
		/**
		 * @var string         $className
		 * @var WebsocketRoute $websocketRoute
		 */
		foreach ($classList as $className => $websocketRoute) {
			$str .= "Router::addServer('{$websocketRoute->server}', function () {
    Router::get('/', '{$className}');
});" . PHP_EOL;
		}
		return $str;
	}

	/**
	 * 获取自定义路由规则信息
	 * @return mixed
	 */
	public function getExtra()
	{
		$str = config('chive.route_extra');
		if (empty($str)) {
			return '';
		}
		return PHP_EOL . $str . PHP_EOL;
	}

	/**
	 * 根据server，控制器和方法，获取地址
	 * @param $server
	 * @param $controller
	 * @param $method
	 */
	public function getMethodRoute($server, $controller, $method)
	{
		if (!isset($this->routes[$server])) {
			return [];
		}
		foreach ($this->routes[$server] as $classRoute) {
			if ($classRoute['controller'] != $controller) {
				continue;
			}
			foreach ($classRoute['function'] as $methodRoute) {
				if ($methodRoute['func'] == $method) {
					return [
						'routeStr' => $classRoute['prefix'] . $methodRoute['funcName'],
						'method'   => (in_array('POST', $methodRoute['method']) ? 'post' : 'get'),
					];
				}
			}
		}
		return [];
	}
}
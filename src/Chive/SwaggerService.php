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
use Chive\Helper\AnnotationHelper;
use Chive\Helper\ErrorHelper;
use Chive\Helper\RouteHelper;
use Closure;
use Doctrine\Common\Annotations\AnnotationReader;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\HttpServer\Router\DispatcherFactory;
use Hyperf\HttpServer\Router\Handler;
use Hyperf\Logger\LoggerFactory;
use Hyperf\Utils\ApplicationContext;
use Hyperf\Utils\Arr;

class SwaggerService
{
	public $config;
	/** @var array 存总的swagger.json文件 */
	public $swagger;
	/** @var array 按tag分开存swagger信息，生成多个swagger方便导入yapi中 */
	public $swaggerBranch;

	public $logger;

	public $server = 'http';

	public function __construct()
	{
		$container     = ApplicationContext::getContainer();
		$this->config  = $container->get(ConfigInterface::class);
		$this->logger  = make(StdoutLoggerInterface::class);
		$this->swagger = $this->config->get('chive.swagger');
		$this->server  = config('chive.swagger_server', 'http');
	}

	public function main()
	{
		if ($this->config->get('chive.create_swagger_enable', false) == false) {
			return;
		}
		$this->mainNoAuth();
	}

	public function mainNoAuth()
	{
		if (empty($this->config->get('chive.swagger_output_path'))) {
			$this->logger->error('/config/autoload/chive.php未定义swagger.json输出路径');
			return;
		}
		$container = ApplicationContext::getContainer();
		$servers   = $this->config->get('server.servers');
		foreach ($servers as $server) {
			if ($server['name'] != $this->server) {
				continue;
			}
			$router = $container->get(DispatcherFactory::class)->getRouter($server['name']);
			$data   = $router->getData();
			if (empty($data)) {
				continue;
			}
			$routesService = make(RoutesService::class);
			$routesService->readClassRoute();

			$swagger = $this;
			$this->initModel();
			array_walk_recursive($data, function ($item) use ($swagger, $routesService) {
				if ($item instanceof Handler && !($item->callback instanceof Closure)) {
					[$controller, $action] = RouteHelper::resolveRoute($item->callback);
					$swagger->addPath($controller, $action, $routesService);
				}
			});
			$this->swagger['tags'] = array_values($this->swagger['tags'] ?? []);

			$this->save();
		}
	}

	/**
	 * @param               $className
	 * @param               $methodName
	 * @param RoutesService $routesService
	 */
	public function addPath($className, $methodName, $routesService)
	{
		$ignores = $this->config->get('annotations.scan.ignore_annotations', []);
		foreach ($ignores as $ignore) {
			AnnotationReader::addGlobalIgnoredName($ignore);
		}

		// 读取注解
		$classAnnotations  = AnnotationHelper::classMetadata($className);
		$methodAnnotations = AnnotationHelper::methodMetadata($className, $methodName);
		// 找到类路由
		$classRoute      = null;
		$definitionsAnno = null;
		$definitionAnno  = null;
		foreach ($classAnnotations as $annotation) {
			if ($annotation instanceof ClassRoute && $this->server == $annotation->server) {
				$classRoute = $annotation;
			}
			if ($annotation instanceof ApiDefinitions) {
				$definitionsAnno = $annotation;
			}
			if ($annotation instanceof ApiDefinition) {
				$definitionAnno = $annotation;
			}
		}
		if (empty($classRoute)) {
			// 没有配置类路由，不解析
			return;
		}

		// 方法路由及入参出参
		$methodRoute = null;
		$responses   = [];
		$params      = [];
		$consumes    = '';
		foreach ($methodAnnotations as $annotation) {
			if ($annotation instanceof MethodRoute && $annotation->server == $this->server) {
				$methodRoute = $annotation;
			} else if ($annotation instanceof Param) {
				$params[] = $annotation;
			} else if ($annotation instanceof ApiResponse) {
				$responses[] = $annotation;
			}
			if ($annotation instanceof FormData) {
				$consumes = 'application/x-www-form-urlencoded';
			}
			if ($annotation instanceof Body) {
				$consumes = 'application/json';
			}
		}

		$this->makeDefinition($definitionsAnno);
		$definitionAnno && $this->makeDefinition([$definitionAnno]);

		// 加入tag
		$tag                         = $classRoute->tag;
		$this->swagger['tags'][$tag] = [
			'name'        => $classRoute->tag,
			'description' => $classRoute->desc,
		];
		// 获取路由
		$route = $routesService->getMethodRoute($this->server, $className, $methodName);

		$path                                   = $route['routeStr'];
		$method                                 = $route['method'];
		$this->swagger['paths'][$path][$method] = [
			'tags'        => [$tag],
			'summary'     => $methodRoute->tag ?? '',
			'description' => $methodRoute->desc ?? '',
			'operationId' => $route['routeStr'],
			'parameters'  => $this->makeParameters($params, $path, $method),
			'produces'    => [
				'application/json',
			],
			'consumes'    => [
				$consumes,
			],
			'responses'   => $this->makeResponses($responses),
		];
	}

	public function getTypeByRule($rule)
	{
		$default = explode('|', preg_replace('/\[.*\]/', '', $rule));

		if (array_intersect($default, ['int', 'lt', 'gt', 'ge', 'integer'])) {
			return 'integer';
		}
		if (array_intersect($default, ['numeric'])) {
			return 'number';
		}
		if (array_intersect($default, ['array'])) {
			return 'array';
		}
		if (array_intersect($default, ['object'])) {
			return 'object';
		}
		if (array_intersect($default, ['file'])) {
			return 'file';
		}
		return 'string';
	}

	public function makeParameters($params, $path, $method)
	{
		$method     = ucfirst($method);
		$path       = str_replace(['{', '}'], '', $path);
		$parameters = [];
		/** @var Param $item */
		foreach ($params as $item) {
			$key = $item->key;
			if (strpos($item->key, '.')) {
				$names = explode('.', $key);
				$key   = array_shift($names);
				foreach ($names as $str) {
					$key .= "[{$str}]";
				}
			}
			$parameters[$item->key] = [
				'in'          => $item->in,
				'name'        => $key,
				'description' => $item->name,
				'required'    => $item->required,
			];
			if ($item instanceof Body) {
				$modelName = $method . implode('', array_map('ucfirst', explode('/', $path)));
				$this->rules2schema($modelName, $item->rules);
				$parameters[$item->key]['schema']['$ref'] = '#/definitions/' . $modelName;
			} else {
				$type = $this->getTypeByRule($item->rule);
				if ($type !== 'array') {
					$parameters[$item->key]['type'] = $type;
				}
				$parameters[$item->key]['default'] = $item->default;
			}
		}
		return array_values($parameters);
	}

	public function makeResponses($responses)
	{
		$templates = $this->config->get('chive.templates', []);

		$resp = [];
		/** @var ApiResponse $item */
		foreach ($responses as $item) {
			// 解析模板
			if ($item->template && Arr::get($templates, $item->template)) {
				$json = json_encode($templates[$item->template], JSON_UNESCAPED_UNICODE);
				if (!$item->data) {
					$item->data = [];
				}
				$template    = str_replace('"{template}"', json_encode($item->data), $json);
				$template    = json_decode($template, true);
				$item->code  = $template[ErrorHelper::RET_CODE];
				$item->msg   = $template[ErrorHelper::RET_MSG];
				$item->total = $template[ErrorHelper::RET_TOTAL];
				$item->data  = $template;
			}
			$resp[$item->code] = [
				'description' => $item->msg ?? '',
			];
			if ($item->data) {
				// 处理直接返回列表的情况 List<Integer> List<String>
				if (isset($item->data[0]) && !is_array($item->data[0])) {
					$resp[$item->code]['schema']['type'] = 'array';
					if (is_int($item->data[0])) {
						$resp[$item->code]['schema']['items'] = [
							'type' => 'integer',
						];
					} elseif (is_string($item->data[0])) {
						$resp[$item->code]['schema']['items'] = [
							'type' => 'string',
						];
					}
					continue;
				}
				// 解析数组
				$resp[$item->code]['schema'] = $this->responseSchemaToDefinition($item->data);
			}
		}
		return $resp;
	}

	public function makeDefinition($definitions)
	{
		if (!$definitions) {
			return;
		}
		if ($definitions instanceof ApiDefinitions) {
			$definitions = $definitions->definitions;
		}
		foreach ($definitions as $definition) {
			/** @var ApiDefinition $definition */
			$defName  = $definition->name;
			$defProps = $definition->properties;

			$formattedProps = [];

			foreach ($defProps as $propKey => $prop) {
				$propKeyArr = explode('|', $propKey);
				$propName   = $propKeyArr[0];
				$propVal    = [];
				isset($propKeyArr[1]) && $propVal['description'] = $propKeyArr[1];
				if (is_array($prop)) {
					if (isset($prop['description']) && is_string($prop['description'])) {
						$propVal['description'] = $prop['description'];
					}

					if (isset($prop['type']) && is_string($prop['type'])) {
						$propVal['type'] = $prop['type'];
					}

					if (isset($prop['default'])) {
						$propVal['default'] = $prop['default'];
						$type               = gettype($propVal['default']);
						if (in_array($type, ['double', 'float'])) {
							$type = 'number';
						}
						!isset($propVal['type']) && $propVal['type'] = $type;
						$propVal['example'] = $propVal['type'] === 'number' ? 'float' : $propVal['type'];
					}
					if (isset($prop['$ref'])) {
						$propVal['$ref'] = '#/definitions/' . $prop['$ref'];
					}
				} else {
					$propVal['default'] = $prop;
					$type               = gettype($prop);
					if (in_array($type, ['double', 'float'])) {
						$type = 'number';
					}
					$propVal['type']    = $type;
					$propVal['example'] = $type === 'number' ? 'float' : $type;
				}
				$formattedProps[$propName] = $propVal;
			}
			$this->swagger['definitions'][$defName]['properties'] = $formattedProps;
		}
	}

	public function responseSchemaToDefinition($schema)
	{
		if (!$schema) {
			return false;
		}
		$definition = [];

		// 处理 Map<String, String> Map<String, Object> Map<String, List>
		$schemaContent = $schema;
		// 处理 List<Map<String, Object>>
		if (isset($schema[0]) && is_array($schema[0])) {
			$schemaContent = $schema[0];
		}
		foreach ($schemaContent as $keyString => $val) {
			$property         = [];
			$property['type'] = gettype($val);
			if (in_array($property['type'], ['double', 'float'])) {
				$property['type'] = 'number';
			}
			$keyArray                = explode('|', $keyString);
			$key                     = $keyArray[0];
			$property['description'] = $keyArray[1] ?? '';

			// 如果设置$ref
			if ($key == '$ref') {
				$property   = [
					'description' => '',
					'$ref'        => '#/definitions/' . $val,
				];
				$definition = $property;
			} else {
				if (is_array($val)) {
					if ($property['type'] === 'array' && isset($val[0])) {
						if (is_array($val[0])) {
							$property['type']  = 'array';
							$ret               = $this->responseSchemaToDefinition($val[0]);
							$property['items'] = $ret;
						} else {
							$property['type']          = 'array';
							$itemType                  = gettype($val[0]);
							$property['items']['type'] = $itemType;
							$property['example']       = [$itemType === 'number' ? 'float' : $itemType];
						}
					} else {
						// definition引用不能有type
						unset($property['type']);
						if (count($val) > 0) {
							$ret      = $this->responseSchemaToDefinition($val);
							$property = $ret;
						} else {
							$property['$ref'] = '#/definitions/ModelObject';
						}
					}
				} else {
					$property['default'] = $val;
					$property['example'] = $property['type'] === 'number' ? 'float' : $property['type'];
				}
				$definition['properties'][$key] = $property;
			}
		}

		return $definition;
	}

	public function putFile(string $file, string $content)
	{
		$pathInfo = pathinfo($file);
		if (!empty($pathInfo['dirname'])) {
			if (file_exists($pathInfo['dirname']) === false) {
				if (mkdir($pathInfo['dirname'], 0644, true) === false) {
					return false;
				}
			}
		}
		return file_put_contents($file, $content);
	}

	public function save()
	{
		$this->putFile($this->config->get('chive.swagger_output_path') . 'swagger.json', json_encode($this->swagger, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
	}

	private function initModel()
	{
		$arraySchema  = [
			'type'     => 'array',
			'required' => [],
			'items'    => [
				'type' => 'string',
			],
		];
		$objectSchema = [
			'type'     => 'object',
			'required' => [],
			'items'    => [
				'type' => 'string',
			],
		];

		$this->swagger['definitions']['ModelArray']  = $arraySchema;
		$this->swagger['definitions']['ModelObject'] = $objectSchema;
	}

	private function rules2schema($name, $rules)
	{
		$schema = [
			'type'       => 'object',
			'properties' => [],
		];
		foreach ($rules as $field => $rule) {
			$type     = null;
			$property = [];

			$fieldNameLabel = explode('|', $field);
			$fieldName      = $fieldNameLabel[0];
			if (strpos($fieldName, '.')) {
				$fieldNames = explode('.', $fieldName);
				$fieldName  = array_shift($fieldNames);
				$endName    = array_pop($fieldNames);
				$fieldNames = array_reverse($fieldNames);
				$newRules   = '{"' . $endName . '|' . $fieldNameLabel[1] . '":"' . $rule . '"}';
				foreach ($fieldNames as $v) {
					if ($v === '*') {
						$newRules = '[' . $newRules . ']';
					} else {
						$newRules = '{"' . $v . '":' . $newRules . '}';
					}
				}
				$rule = json_decode($newRules, true);
			}
			if (is_array($rule)) {
				$deepModelName = $name . ucfirst($fieldName);
				if (Arr::isAssoc($rule)) {
					$this->rules2schema($deepModelName, $rule);
					$property['$ref'] = '#/definitions/' . $deepModelName;
				} else {
					$type = 'array';
					$this->rules2schema($deepModelName, $rule[0]);
					$property['items']['$ref'] = '#/definitions/' . $deepModelName;
				}
			} else {
				$type = $this->getTypeByRule($rule);
				if ($type === 'string') {
					in_array('required', explode('|', $rule)) && $schema['required'][] = $fieldName;
				}
				if ($type == 'array') {
					$property['$ref'] = '#/definitions/ModelArray';
				}
				if ($type == 'object') {
					$property['$ref'] = '#/definitions/ModelObject';
				}
			}
			if ($type !== null) {
				$property['type'] = $type;
				if (!in_array($type, ['array', 'object'])) {
					$property['example'] = $type;
				}
			}
			$property['description'] = $fieldNameLabel[1] ?? '';

			$schema['properties'][$fieldName] = $property;
		}
		$this->swagger['definitions'][$name] = $schema;
	}
}

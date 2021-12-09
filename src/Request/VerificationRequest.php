<?php
/**
 * Class VerificationRequest
 * 作者: su
 * 时间: 2021/4/22 10:33
 * 备注: 修改$request->all()方法，自动根据注解验证规则验证参数
 */

namespace Chive\Request;


use Chive\Annotation\Form\FormData;
use Chive\Constants\CommonConstants;
use Chive\Exception\BusinessException;
use Chive\Helper\ErrorHelper;
use Chive\Helper\RouteHelper;
use Doctrine\Common\Annotations\AnnotationReader;
use Hyperf\Di\Annotation\Inject;
use Hyperf\Di\ReflectionManager;
use Hyperf\HttpServer\Request;
use Hyperf\Utils\Arr;
use Hyperf\Utils\Context;
use Hyperf\Utils\Str;
use Hyperf\Validation\Contract\Rule;
use Hyperf\Validation\Contract\ValidatorFactoryInterface;
use Hyperf\Validation\ValidatorFactory;

class VerificationRequest extends Request
{
	/**
	 * @var ValidatorFactoryInterface
	 */
	protected $validationFactory;

	public function __construct(ValidatorFactoryInterface $v)
	{
		$this->validationFactory = $v;
	}


	/**
	 * 重写all()方法，在父类基础上增加验证参数，和保留声明参数
	 * @return array
	 */
	public function all(): array
	{
		$data = parent::all();
		// 如果获取不到参数，从grpc设置的上下文中获取参数
		if (empty($data)) {
			$data = Context::get(CommonConstants::GRPC_REQUEST_KEY, []);
		}
		$route = RouteHelper::getRoute($this);
		if (empty($route)) {
			return $data;
		}
		list($controller, $method) = $route;
		return $this->validated($controller, $method, $data);
	}

	/**
	 * 验证方法
	 * @param string $controller
	 * @param string $method
	 * @param array  $params
	 * @return array
	 */
	public function validated($controller, $method, $params)
	{
		$reflectMethod = ReflectionManager::reflectMethod($controller, $method);
		$annotations   = make(AnnotationReader::class)->getMethodAnnotations($reflectMethod);

		// 没有注解，返回原参数
		if (empty($annotations)) {
			return $params;
		}

		// 组装规则
		$formDataRule = [];            // [key=>规则]
		$map          = [];            // [key=>key对应名字]

		foreach ($annotations as $annotation) {
			if ($annotation instanceof FormData) {
				$formDataRule[$annotation->key] = $annotation->rule;
				$map[$annotation->key]          = $annotation->name;
			}
			// todo 更多规则待写 2021-4-23
		}
		// 没定义规则，返回原参数
		if (empty($formDataRule)) {
			return $params;
		}

		return $this->check($params, $formDataRule, $map, $controller);
	}

	/**
	 * 获取request对象
	 * @param string $controllr 控制器名,如：App\Controller\DemoController
	 * @return string 返回对应的request对象
	 */
	public function getRequestObj($controllr)
	{
		$name = $this->getRequestName($controllr);
		if (!class_exists($name)) {
			throw new BusinessException(ErrorHelper::FAIL_CODE, $controllr . '使用"cb_"验证规则，需要定义' . $name . '文件');
		}
		try {
			$obj = new $name();
		} catch (\Throwable $e) {
			throw new BusinessException(ErrorHelper::FAIL_CODE, '实例化类' . $name . '失败', $e);
		}
		return $obj;
	}

	/**
	 * 获取request名
	 * @param string $controller 控制器名,如：App\Controller\DemoController
	 * @return string 返回对应的request名，App\Request\DemoRequest
	 */
	public function getRequestName($controller)
	{
		$classArr  = explode("\\", $controller);
		$className = $classArr[count($classArr) - 1];
		if (strpos($className, 'Controller') !== false) {
			$className = substr($className, 0, strlen($className) - 10);
		} else {
			throw new BusinessException(ErrorHelper::FAIL_CODE, '自动验证失败，控制器必须以Controller为后缀');
		}
		$requestName = 'Request' . '\\' . $className . 'Request';
//		for ($i = (count($classArr) - 3); $i >= 0; $i--) {
//			$requestName = $classArr[$i] . '\\' . $requestName;
//		}
		// su.2021-8-4.修改，直接第一级目录拼接后面的目录
		$requestName = $classArr[0] . '\\' . $requestName;
		return $requestName;
	}

	/**
	 * 检查参数
	 * @param array $params 参数 [key=>val]
	 * @param array $rule   规则 [key=>rule]
	 * @param array $map    参数对应名 [key=>名称]
	 * @param null  $controller
	 * @return array
	 */
	public function check($params, $rule, $map, $controller = null)
	{
		$_rule      = [];
		$requestObj = null;
		// 验证其他自定义函数
		foreach ($rule as $key => $ruleStr) {
			$ruleArr = explode("|", $ruleStr);
			foreach ($ruleArr as $k => $v) {
				if (Str::startsWith($v, 'cb_')) {
					if (empty($requestObj)) {
						$requestObj = $this->getRequestObj($controller);
					}
					$method = Str::replaceFirst('cb_', '', $v);
					if (!method_exists($requestObj, $method)) {
						$requestName = $this->getRequestName($controller);
						throw new BusinessException(ErrorHelper::FAIL_CODE, $requestName . '未定义验证方法' . $method);
					}
					$ruleArr[$k] = $this->makeObjectCallback($method, $requestObj);
				}
			}
			$_rule[$key] = $ruleArr;

			// 将$rule转换为数组，后面做转换参数类型用
			if (strpos((string)$key, '.') !== false) {
				Arr::set($rule, $key, $ruleStr);
				unset($rule[$key]);
			}
		}

		$validator = $this->validationFactory->make($params, $_rule, [], $map);
		if ($validator->fails()) {
			throw new BusinessException(ErrorHelper::FAIL_CODE, implode(",", $validator->errors()->all()));
		}

		$data = $validator->validated();
		$data = self::tranParams($rule, $data);
		return $data;
	}

	/**
	 * 根据规则转换字段类型
	 * @param $rule
	 * @param $params
	 * @return array
	 */
	public static function tranParams($rule, $params)
	{
		foreach ($params as $key => &$val) {
			if (!isset($rule[$key])) {
				continue;
			}
			if (is_array($val)) {
				$val = self::tranParams($rule[$key], $val);
			} else {
				$ruleList = $rule[$key];
				if (is_string($rule[$key])) $ruleList = explode('|', $rule[$key]);
				if (in_array('integer', $ruleList)) $val = intval($val);
				if (in_array('string', $ruleList)) $val = strval($val);
			}
		}
		return $params;
	}

	/**
	 * 创建匿名规则对象，执行指定验证方法
	 * @param $method
	 * @param $object
	 * @return Rule
	 */
	public function makeObjectCallback($method, $object)
	{
		return new class($method, $this, $object) implements Rule {

			use \Hyperf\Di\Aop\ProxyTrait;
			use \Hyperf\Di\Aop\PropertyHandlerTrait;

			public $custom_rule;

			public $validation;

			public $object;

			public $error = '';

			public $attribute;

			public function __construct($custom_rule, $validation, $object)
			{
				$this->custom_rule = $custom_rule;
				$this->validation  = $validation;
				$this->object      = $object;
			}

			public function passes($attribute, $value): bool
			{
				$this->attribute = $attribute;
				$rule            = $this->custom_rule;
				if (strpos($rule, ':') !== false) {
					$rule  = explode(':', $rule)[0];
					$extra = explode(',', explode(':', $rule)[1]);
					$ret   = $this->object->{$rule}($attribute, $value, $extra);
					if (is_string($ret)) {
						$this->error .= $ret;
						return false;
					}
					return true;
				}
				$ret = $this->object->{$rule}($attribute, $value);
				if (is_string($ret)) {
					$this->error .= $ret;
					return false;
				}
				return true;
			}

			public function message()
			{
//				return sprintf($this->error, $this->attribute);
				return $this->error;
			}
		};
	}
}
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

namespace Chive\Annotation\Form;

use Hyperf\Di\Annotation\AbstractAnnotation;

abstract class Param extends AbstractAnnotation
{
	public $in;

	/** @var string 变量名 */
	public $key = '';
	/** @var string 变量名中文注释 */
	public $name = '';
	/** @var string 格式：key|name，自动赋值$key|$name变量。 */
	public $param = '';
	/** @var string
	 * 验证规则：同hyperf验证器规则。
	 * 另外cb_funcName，自动调用DemoController对应的DemoRqurest类下funcName()方法
	 */
	public $rule;
	/** @var String 默认值 */
	public $default;

	/**
	 * readonly.
	 * @var bool 是否必填
	 */
	public $required;

	/**
	 * readonly.
	 * @var string 变量类型 string|integer
	 */
	public $type;

	public function __construct($value = null)
	{
		parent::__construct($value);
		$this->setKey()->setName()->setRequire()->setType();
	}

	/**
	 * 设置key的备注，优先级name>param
	 * 如果赋值param，则判断name是否赋值，如果赋值则使用name。如果没有，则赋值param[1]参数
	 * @return $this
	 */
	public function setName()
	{
		if ($this->param) {
			$this->name = $this->name ? $this->name : explode('|', $this->param)[1] ?? '';
		}
		return $this;
	}

	/**
	 * 同上，同name规则
	 * @return $this
	 */
	public function setKey()
	{
		if ($this->param) {
			$this->key = $this->key ? $this->key : explode('|', $this->param)[0] ?? '';
		}
		return $this;
	}

	public function setRequire()
	{
		$this->required = in_array('required', explode('|', $this->rule));
		return $this;
	}

	public function setType()
	{
		$type = 'string';
		if (strpos($this->rule, 'int') !== false) {
			$type = 'integer';
		}
		$this->type = $type;
		return $this;
	}
}

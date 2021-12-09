<?php
/**
 * Class Definitions
 * 作者: su
 * 时间: 2021/4/29 9:43
 * 备注:
 */

namespace Chive\Annotation\Form;


use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class Definitions extends AbstractAnnotation
{
	/**
	 * @var array
	 */
	public $definitions;

	public function __construct($value = null)
	{
		$this->bindMainProperty('definitions', $value);
	}
}
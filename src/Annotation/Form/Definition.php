<?php
/**
 * Class Definition
 * 作者: su
 * 时间: 2021/4/29 9:43
 * 备注:
 */

namespace Chive\Annotation\Form;


use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * @Annotation
 * @Target({"ALL"})
 */
class Definition extends AbstractAnnotation
{
	public $name;

	public $properties;
}
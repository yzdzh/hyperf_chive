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

/**
 * @Annotation
 * @Target({"METHOD"})
 */
class ApiResponse extends AbstractAnnotation
{
	/**
	 * @var 响应码
	 */
    public $code = 0;

	/**
	 * @var 响应提示信息
	 */
    public $msg = '';

	/**
	 * @var 数据
	 */
    public $data = [];

	/**
	 * @var 模板，模板会替换掉code、msg、total字段信息
	 */
    public $template = '';

	/**
	 * @var int 条数
	 */
    public $total = 1;
}

<?php
/**
 * Class ResponseData
 * 作者: su
 * 时间: 2021/4/26 9:42
 * 备注:
 */

namespace Chive\Annotation\Form;

/**
 * 表单返回数据格式定义
 * @Annotation
 * @Target({"METHOD"})
 */
class ResponseData
{
	/**
	 * @var string 错误码
	 */
	public $code = '';

	/**
	 * @var string 报错信息
	 */
	public $msg = '';

	/**
	 * @var array 返回数据
	 */
	public $data = [];

	/**
	 * @var int 行数
	 */
	public $total = 1;
}
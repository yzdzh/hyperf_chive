<?php
/**
 * Class Testing
 * 作者: su
 * 时间: 2021/8/17 17:21
 * 备注: 测试注解，本注解会根据FormData注解获取默认测试参数
 */

namespace Chive\Annotation\Test;

use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class Testing extends AbstractAnnotation
{
	/**
	 * @var bool 测试前默认清空数据库，true清空，false不清空。默认为false
	 */
	public $flushDb = false;

	/**
	 * @var string 清空数据库方式，
	 * init在执行controller前清空一次，
	 * all执行所有方法前清空一次。
	 */
	public $model = 'init';

	/**
	 * @var string 数据库Model名(Model文件下类名)，不填默认为controller名字，
	 * 需要清空多个库，逗号分隔填多个
	 */
	public $dbName = '';
}
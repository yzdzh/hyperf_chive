<?php

declare(strict_types=1);
/**
 * Created by PhpStorm.
 * User: laizhili
 * Date: 2020/4/21
 * Time: 10:20 下午
 */

namespace Chive\Model;

use Chive\Helper\ErrorHelper;

/**
 * 公共工具类
 * Trait Common
 * @package App\Model
 */
trait Common
{

	/**
	 * 生成长度为16的随机数
	 * @return string
	 */
	public static function randNo()
	{
		$min = pow(10, (6 - 1));
		$max = pow(10, 6) - 1;
		mt_srand(); //重新播种
		return time() . mt_rand($min, $max);
	}

	/**
	 * 计算一个一维数组中的总和值
	 * @param $array
	 * @return int|mixed
	 */
	public static function arrayTotal($array)
	{
		$total = 0;
		foreach ($array as $value) {
			$total += abs($value);
		}
		return $total;
	}

	/**
	 * 返回格式转换
	 * @param array $list
	 * @return array
	 */
	static public function returnList($list = [])
	{
		if (empty($list) || !is_array($list)) return [];
		$data          = [];
		$data['data']  = !empty($list['data']) ? $list['data'] : [];
		$data['total'] = !empty($list['total']) ? $list['total'] : 0;
		return $data;
	}

	/**
	 * Mongo列表结果格式返回转换
	 * @param array $list
	 * @return array
	 */
	public static function returnMongoList($list = [])
	{
		if (empty($list) || !is_array($list)) return [];
		$data          = [];
		$data['data']  = !empty($list['list']) ? $list['list'] : [];
		$data['total'] = !empty($list['totalCount']) ? $list['totalCount'] : 0;
		return $data;
	}

	/**
	 * 时间戳转时间格式
	 * @param $time
	 * @return false|string
	 */
	public static function dateTime($time)
	{
		return $time ? date('Y/m/d H:i:s', (int)$time) : '';
	}

	/**
	 * 存model字段
	 * @var array
	 */
	protected static $modelFields = [];

	/**
	 * 获取model属性
	 * @param string $class model类名
	 * @return array
	 */
	public static function getModelProperty($class)
	{
		if (isset(self::$modelFields[$class])) {
			return self::$modelFields[$class];
		}
		$property = [];
		try {
			$obj = new \ReflectionClass($class);
			$doc = $obj->getDocComment();
			$arr = explode("*", $doc);
			foreach ($arr as $str) {
				if (strpos($str, "@property") !== false) {
					$list       = explode("$", $str);
					$list       = explode(" ", $list[1]);
					$property[] = trim($list[0]);
				}
			}
		} catch (\ReflectionException $e) {
		}
		self::$modelFields[$class] = $property;
		return $property;
	}
}
<?php
/**
 * Class AnnotationHelper
 * 作者: su
 * 时间: 2021/4/26 15:38
 * 备注: 注解助手类
 */

namespace Chive\Helper;

use Doctrine\Common\Annotations\AnnotationReader;
use Hyperf\Di\Annotation\AnnotationCollector;
use Hyperf\Di\ReflectionManager;

class AnnotationHelper
{
	/**
	 * 读取指定类注解（注解可重复）
	 * @param $className
	 * @return array|mixed
	 */
	public static function classMetadata($className)
	{
		$reflectClass = ReflectionManager::reflectClass($className);
		$reader       = new AnnotationReader();
		return $reader->getClassAnnotations($reflectClass);
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
	 * 获取类注解（注解不重复）
	 * @param $className
	 * @return array|mixed
	 */
	public static function classAnnotations($className)
	{
		return AnnotationCollector::list()[$className]['_c'] ?? [];
	}
}
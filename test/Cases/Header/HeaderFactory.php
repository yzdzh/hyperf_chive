<?php
/**
 * Class HeaderFactory
 * 作者: su
 * 时间: 2021/10/29 18:16
 * 备注:
 */

namespace HyperfTest\Cases\Header;

/**
 * header类单例工厂
 */
class HeaderFactory
{
    /**
     * 存储header对象
     * @var array [className => <AbstractHeader>]
     */
    private static $headers = [];

    /**
     * 创建header类
     * @param $className
     * @return AbstractHeader
     */
    public static function create($className)
    {
        if(isset(self::$headers[$className])) {
            return self::$headers[$className];
        }
        /** @var AbstractHeader $header */
		$header = new $className();
        $header->process();
		self::$headers[$className] = $header;
		return $header;
    }
}
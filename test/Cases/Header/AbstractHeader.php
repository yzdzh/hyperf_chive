<?php
/**
 * Class AbstratorHeader
 * 作者: su
 * 时间: 2021/10/29 18:15
 * 备注:
 */

namespace HyperfTest\Cases\Header;

/**
 * 抽象header获取类
 */
abstract class AbstractHeader
{
    /**
     * 存储header对象
     * @var array
     */
	protected $header = [];

	/**
	 * 调用应用设置token
	 * @return mixed
	 */
    abstract public function process();

    /**
     * @return array
     */
    public function getHeader()
    {
        return $this->header;
    }
}
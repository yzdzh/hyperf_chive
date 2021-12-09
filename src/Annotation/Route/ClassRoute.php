<?php
/**
 * Class ClassRoute
 * 作者: su
 * 时间: 2021/1/4 18:01
 * 备注: 类路由
 */

namespace Chive\Annotation\Route;


use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * @Annotation
 * @Target({"CLASS"})
 */
class ClassRoute extends AbstractAnnotation
{

    /**
     * @var string 前缀
     *             例子：MyDataController	@ ClassRoute(prefix="/data")	/data/index
     */
    public $prefix = '';

    /**
     * @var string 可请求方法，默认只能post请求，修改写法“GET,POST”，逗号分隔字符串
     */
    public $methods = '';

    /**
     * @var string 指定server，默认http
     */
    public $server = 'http';

    /**
     * @var string 中间件，可填多个中间件，“,”分隔
     */
    public $middleware = '';

	/**
	 * @var string 控制器名
	 */
    public $tag = '';

	/**
	 * @var string 控制器解析文本
	 */
    public $desc = '';
}
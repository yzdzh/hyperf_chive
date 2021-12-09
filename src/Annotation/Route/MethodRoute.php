<?php
/**
 * Class MethodRoute
 * 作者: su
 * 时间: 2021/1/4 18:19
 * 备注:
 */

namespace Chive\Annotation\Route;


use Hyperf\Di\Annotation\AbstractAnnotation;

/**
 * 方法路由和类路由可以一起使用，MethodRoute和ClassRoute一起使用，MethodRoute优先级更高
 * @Annotation
 * @Target({"METHOD"})
 */
class MethodRoute extends AbstractAnnotation
{

	/**
	 * @var string 指定server，默认http
	 */
	public $server = 'http';

    /**
     * @var string 重写指定路径，
     * 如果有设置ClassRoute->prefix，则自动拼接prefix.path。如果没设置路由直接为path
     */
    public $path = '';

    /**
     * @var string 可请求方法，默认只能post请求，修改写法“GET,POST”，逗号分隔字符串
     *             覆盖ClassRoute->methods方法
     */
    public $methods = '';

    /**
     * @var string 中间件,只应用当前方法，可填多个中间件，“,”分隔
     */
    public $middleware = '';

	/**
	 * @var string 方法名
	 */
	public $tag = '';

	/**
	 * @var string 解析文本（备注）
	 */
	public $desc = '';
}
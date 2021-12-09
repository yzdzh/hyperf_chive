<?php
/**
 * Class RouteHelper
 * 作者: su
 * 时间: 2021/4/23 18:05
 * 备注: 路由工具
 */

namespace Chive\Helper;


use FastRoute\Dispatcher;
use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Router\Dispatched;

class RouteHelper
{

	/**
	 * 获取路由
	 * @return array [controller, method]
	 */
	public static function getRoute(RequestInterface $request)
	{
		/** @var Dispatched $dispatched */
		$dispatched = $request->getAttribute(Dispatched::class);
		if ($dispatched->status !== Dispatcher::FOUND) {
			return [];
		}
		if ($dispatched->handler->callback instanceof \Closure) {
			return [];
		}
		return self::resolveRoute($dispatched->handler->callback);
	}

	/**
	 * 转换$dispatched->handler->route中的路由规则
	 * @param $handler
	 * @return array [controller, method]
	 */
	public static function resolveRoute($handler): array
	{
		if (is_string($handler)) {
			if (strpos($handler, '@') !== false) {
				return explode('@', $handler);
			}
			return explode('::', $handler);
		}
		if (is_array($handler) && isset($handler[0], $handler[1])) {
			return $handler;
		}
		throw new \RuntimeException('Handler not exist.');
	}

}
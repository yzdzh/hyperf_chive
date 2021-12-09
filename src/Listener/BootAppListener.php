<?php
/**
 * Class CreateRouteListener
 * 作者: su
 * 时间: 2021/4/19 14:42
 * 备注: 服务启动前创建路由
 */

namespace Chive\Listener;

use Chive\Chive\SwaggerService;
use Chive\Chive\CreatePathService;
use Chive\Chive\SwaggerBranchService;
use Chive\Chive\SwaggerUIService;
use Chive\Command\RoutesCommand;
use Chive\Chive\RoutesService;
use Hyperf\Event\Contract\ListenerInterface;
use Hyperf\Framework\Event\BootApplication;

class BootAppListener implements ListenerInterface
{
	public function listen(): array
	{
		return [
			BootApplication::class,
		];
	}

	public function process(object $event)
	{
		// 创建路由文件
		make(RoutesService::class)->main();
		// 创建资源目录
		make(CreatePathService::class)->main();
		// 生成swagger.json文件
		make(SwaggerService::class)->main();
		make(SwaggerBranchService::class)->main();
		// 启动swagger UI
//		make(SwaggerUIService::class)->main();
	}
}
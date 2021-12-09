<?php
/**
 * Class CreatePathService
 * 作者: su
 * 时间: 2021/4/25 17:13
 * 备注: 创建资源目录
 */

namespace Chive\Chive;


use Hyperf\Contract\StdoutLoggerInterface;

class CreatePathService
{
	/**
	 * 服务启动时，根据配置创建临时的资源目录
	 */
	public function main()
	{
		$enable = config('chive.create_resource_path_enable', false);
		if ($enable != true) {
			return;
		}

		$paths = config('chive.resource_paths', []);
		if (empty($paths)) {
			return;
		}
		$auth = intval(config('chive.resource_path_auth', 0777));

		foreach ($paths as $path) {
			if(!is_dir($path)) {
				@mkdir($path, $auth);
			}
		}
		make(StdoutLoggerInterface::class)->info('chive自动生成资源目录完成!');
	}
}
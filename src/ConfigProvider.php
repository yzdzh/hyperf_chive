<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */

namespace Chive;

use Chive\Command\CreateCommand;
use Chive\Command\RequestCommand;
use Chive\Command\RoutesCommand;
use Chive\Command\SwaggerRespCommand;
use Chive\Command\UICommand;
use Chive\Command\YapiCommand;
use Chive\Command\YapiRespCommand;
use Chive\Listener\BootAppListener;
use Chive\Listener\CreatePathListener;
use Chive\Listener\CreateRouteListener;

class ConfigProvider
{
	public function __invoke(): array
	{
		return [
			'commands'     => [
				UICommand::class,
				RequestCommand::class,
				RoutesCommand::class,
				YapiRespCommand::class,
				SwaggerRespCommand::class,
				CreateCommand::class,
			],
			'dependencies' => [
//				\Hyperf\HttpServer\Router\DispatcherFactory::class => DispatcherFactory::class,
			],
			'listeners'    => [
				BootAppListener::class,
			],
			'annotations'  => [
				'scan' => [
					'paths' => [
						__DIR__,
					],
				],
			],
			'publish'      => [
				[
					'id'          => 'config',
					'description' => 'The config for chive.',
					'source'      => __DIR__ . '/../publish/chive.php',
					'destination' => BASE_PATH . '/config/autoload/chive.php',
				],
			],
		];
	}
}

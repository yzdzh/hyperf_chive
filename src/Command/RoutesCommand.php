<?php

declare(strict_types=1);

namespace Chive\Command;

use Chive\Annotation\Route\ClassRoute;
use Chive\Annotation\Route\MethodRoute;
use Chive\Annotation\Route\WebsocketRoute;
use Chive\Chive\RoutesService;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Command\Annotation\Command;
use Hyperf\Di\Annotation\AnnotationCollector;
use Psr\Container\ContainerInterface;

/**
 * @Command
 */
class RoutesCommand extends HyperfCommand
{
	public function __construct(string $name = null)
	{
		if ($name == null) {
			$name = 'chive:route';
		}
		parent::__construct($name);
	}

	public function configure()
	{
		parent::configure();
		$this->setDescription('生成路由命令');
	}

	public function handle()
	{
		make(RoutesService::class)->mainNoAuth();
	}
}

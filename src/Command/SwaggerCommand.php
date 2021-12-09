<?php
/**
 * Class SwaggerCommand
 * 作者: su
 * 时间: 2021/6/2 14:46
 * 备注: 生成swagger.json命令
 */

namespace Chive\Command;


use Chive\Chive\SwaggerBranchService;
use Chive\Chive\SwaggerService;
use Chive\Helper\DirHelper;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Symfony\Component\Console\Input\InputOption;

/**
 * @Command
 */
class SwaggerCommand extends HyperfCommand
{
	public function __construct()
	{
		parent::__construct('chive:swagger');
	}

	public function configure()
	{
		parent::configure();
		$this->setDescription('生成swagger.json文件');
	}

	public function handle()
	{
		$path = config('chive.swagger_output_path');
		DirHelper::rmdirs($path);
		make(SwaggerService::class)->mainNoAuth();
		make(SwaggerBranchService::class)->mainNoAuth();
		$this->line("生成swagger.json完成!【{$path}】", 'info');
	}
}
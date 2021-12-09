<?php
/**
 * Class UICommand
 * 作者: su
 * 时间: 2021/5/7 9:26
 * 备注:
 */

namespace Chive\Command;

use Chive\Chive\SwaggerUIService;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Hyperf\Contract\ConfigInterface;
use Hyperf\Contract\StdoutLoggerInterface;
use Hyperf\Utils\ApplicationContext;
use Symfony\Component\Console\Input\InputOption;

/**
 * @Command
 */
class UICommand extends HyperfCommand
{
	public function __construct()
	{
		parent::__construct('chive:ui');
	}

	public function configure()
	{
		parent::configure();
		$this->setDescription('运行 swagger UI 服务器');
	}

	public function handle()
	{
		make(SwaggerUIService::class)->handle($this->input->getOption('port'));
	}

	protected function getArguments()
	{
		$this->addOption('port', 'p', InputOption::VALUE_OPTIONAL, 'Which port you want the SwaggerUi use.', config('chive.start_swagger_port'));
	}
}
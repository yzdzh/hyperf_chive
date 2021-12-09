<?php
/**
 * Class SwaggerRespCommand
 * 作者: su
 * 时间: 2021/5/29 10:02
 * 备注: 根据数据库生成@ApiResponse结构信息
 */

namespace Chive\Command;

use Chive\Chive\SwaggerRespService;
use Chive\Exception\BusinessException;
use Chive\Helper\DirHelper;
use Chive\Helper\ErrorHelper;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Symfony\Component\Console\Input\InputOption;

/**
 * @Command
 */
class SwaggerRespCommand extends HyperfCommand
{
	/** @var string 文件生成目录 */
	protected $path = 'runtime/swaggerResp/';

	public function __construct()
	{
		parent::__construct('chive:swaggerResp');
	}

	public function configure()
	{
		parent::configure();
		$this->setDescription('根据数据库表生成注解ApiResponse格式数据');
	}

	public function handle()
	{
		$dbName = env('DB_DATABASE');
		if (empty($dbName)) {
			throw new BusinessException(ErrorHelper::FAIL_CODE, '未配置数据库信息');
		}
		$list = SwaggerRespService::decodeTables($dbName);
		$path = $this->input->getOption('path');
		$this->createFile($path, $list);
		$this->line('生成文件完成，路径【' . $path . '】', 'info');
	}

	public function createFile($path, $list)
	{
		DirHelper::mkdirs($path);
		foreach ($list as $key => $str) {
			file_put_contents($path . $key . '.txt', $str);
		}
	}

	protected function getArguments()
	{
		$this->addOption('path', 'p', InputOption::VALUE_OPTIONAL, '文件生成路径，默认【' . $this->path . '】.', $this->path);
	}
}
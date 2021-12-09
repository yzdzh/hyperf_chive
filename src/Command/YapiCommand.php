<?php
/**
 * Class YapiUploadCommand
 * 作者: l
 * 时间:
 * 备注: 上传接口数据到远程yapi服务命令
 */

namespace Chive\Command;


use Chive\Chive\SwaggerBranchService;
use Chive\Chive\SwaggerService;
use Chive\Chive\YapiService;
use Hyperf\Command\Annotation\Command;
use Hyperf\Command\Command as HyperfCommand;
use Symfony\Component\Console\Input\InputOption;

/**
 * @Command
 */
class YapiCommand extends HyperfCommand
{

    private static $help  = <<<EOF

  帮助信息:

  -c            [必填]控制器名（无需加Controller.php部分）
  -f            [非必填]方法名
  
  例：
  php bin/hyperf.php chive:yapi -c Test -f getList


EOF;
    private static $error = <<<EOF

  【必填项】
  -c            [必填]控制器名（无需加Controller.php部分）
  -f            [非必填]方法名

EOF;


	public function __construct()
	{
        ! defined('BASE_PATH') && define('BASE_PATH', dirname(__DIR__, 1));

		parent::__construct('chive:yapi');
	}

	public function configure()
	{
		parent::configure();
		$this->setDescription('上传接口数据到远程yapi服务命令');
	}

	public function handle()
	{

        $opt = $this->input->getOptions();

        // 验证字段
        if (!isset($opt["controller"])) {
            $this->line(self::$error, 'info');
            return false;
        }

        (new YapiService())::setOptions($opt);

		$return_url = make(YapiService::class)->mainNoAuth();
		$this->line("命令执行完毕".(!empty($return_url)?",项目地址：".$return_url:''),'info');
	}

    protected function getArguments()
    {
        $this->addOption('controller', 'c', InputOption::VALUE_OPTIONAL, '[必填]控制器名（无需加Controller.php部分）');
        $this->addOption('function', 'f', InputOption::VALUE_OPTIONAL, '[非必填]方法名');
    }

    
}